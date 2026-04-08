<?php

declare(strict_types=1);

namespace LidingoCustomisation\Integrations\ServiceInfo;

use LidingoCustomisation\Helper\ServiceInfoStatus;
use ModularityServiceInfo\Cron\UnpublishExpiredPosts;
use WP_Hook;

class ServiceInfoIntegration
{
    private const MENU_ITEM_TYPE = 'service-info';
    private const MENU_ITEM_OBJECT = 'service-info-archive';
    private const POST_TYPE = 'service_information';
    private const CRON_HOOK = 'modularity_service_info_unpublish_expired';

    public function addHooks(): void
    {
        add_filter('wp_get_nav_menu_items', [$this, 'overrideMenuBadge'], 20, 3);
        add_action('init', [$this, 'replaceUnpublishCronHandler'], 20);
    }

    public function overrideMenuBadge(array $items, mixed $menu, array $args): array
    {
        if (empty($items) || is_admin()) {
            return $items;
        }

        $count = ServiceInfoStatus::getCurrentCount();

        foreach ($items as $item) {
            if (!$this->isServiceInfoMenuItem($item) || !isset($item->title) || !is_string($item->title)) {
                continue;
            }

            $item->title = $this->stripExistingBadgeMarkup($item->title);

            if ($count > 0) {
                $item->title .= $this->renderBadge($count);
            }
        }

        return $items;
    }

    public function replaceUnpublishCronHandler(): void
    {
        $this->removeModularityCronCallback();

        if (!has_action(self::CRON_HOOK, [$this, 'handleUnpublishCron'])) {
            add_action(self::CRON_HOOK, [$this, 'handleUnpublishCron']);
        }
    }

    public function handleUnpublishCron(): void
    {
        $this->unpublishExpiredPosts(100);
    }

    private function unpublishExpiredPosts(int $limit): void
    {
        $query = new \WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => 'unpublish_automatically',
                    'value' => '1',
                    'compare' => '=',
                ],
            ],
        ]);

        if (!$query->have_posts()) {
            return;
        }

        $currentTime = current_time('timestamp');

        while ($query->have_posts()) {
            $query->the_post();
            $postId = get_the_ID();

            if (!is_int($postId) || $postId <= 0) {
                continue;
            }

            $deadlineTimestamp = $this->getDeadlineTimestamp($postId);
            if ($deadlineTimestamp === null || $deadlineTimestamp > $currentTime) {
                continue;
            }

            wp_update_post([
                'ID' => $postId,
                'post_status' => $this->getUnpublishAction($postId),
            ]);
        }

        wp_reset_postdata();
    }

    private function getDeadlineTimestamp(int $postId): ?int
    {
        $deadlineRaw = $this->getFieldValue('unpublish_date', $postId);

        if (!is_string($deadlineRaw) || trim($deadlineRaw) === '') {
            $deadlineRaw = $this->getFieldValue('end_date', $postId);
        }

        if (!is_string($deadlineRaw) || trim($deadlineRaw) === '') {
            return null;
        }

        try {
            $dateTime = new \DateTimeImmutable($deadlineRaw, wp_timezone());
        } catch (\Exception) {
            return null;
        }

        return $dateTime->getTimestamp();
    }

    private function getUnpublishAction(int $postId): string
    {
        $action = $this->getFieldValue('on_unpublish', $postId);

        if (!is_string($action) || !in_array($action, ['draft', 'trash'], true)) {
            return 'draft';
        }

        return $action;
    }

    private function getFieldValue(string $fieldName, int $postId): mixed
    {
        if (function_exists('get_field')) {
            return get_field($fieldName, $postId);
        }

        return get_post_meta($postId, $fieldName, true);
    }

    private function removeModularityCronCallback(): void
    {
        if (!class_exists(UnpublishExpiredPosts::class)) {
            return;
        }

        global $wp_filter;

        if (
            !isset($wp_filter[self::CRON_HOOK]) ||
            !($wp_filter[self::CRON_HOOK] instanceof WP_Hook)
        ) {
            return;
        }

        foreach ($wp_filter[self::CRON_HOOK]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $callable = $callback['function'] ?? null;

                if (
                    !is_array($callable) ||
                    !isset($callable[0], $callable[1]) ||
                    !$callable[0] instanceof UnpublishExpiredPosts ||
                    $callable[1] !== 'handleCron'
                ) {
                    continue;
                }

                remove_action(self::CRON_HOOK, $callable, (int) $priority);
            }
        }
    }

    private function isServiceInfoMenuItem(mixed $item): bool
    {
        if (!is_object($item)) {
            return false;
        }

        $type = isset($item->type) && is_string($item->type) ? $item->type : '';
        $object = isset($item->object) && is_string($item->object) ? $item->object : '';

        if ($type === self::MENU_ITEM_TYPE || $object === self::MENU_ITEM_OBJECT) {
            return true;
        }

        if (!isset($item->classes) || !is_array($item->classes)) {
            return false;
        }

        return in_array('s-post-type-service-info-archive', $item->classes, true);
    }

    private function stripExistingBadgeMarkup(string $title): string
    {
        $title = preg_replace('/<esi:include\b[^>]*\/>/i', '', $title);
        $title = preg_replace('/<span\b[^>]*class=(["\'])[^"\']*service-info-badge[^"\']*\1[^>]*>.*?<\/span>/is', '', $title);

        return trim((string) $title);
    }

    private function renderBadge(int $count): string
    {
        return sprintf(
            '<span class="service-info-badge" aria-label="%s">%d</span>',
            esc_attr(sprintf(
                /* translators: %d: number of current service issues. */
                _n('%d aktiv driftstörning', '%d aktiva driftstörningar', $count, 'lidingo-customisation'),
                $count
            )),
            $count
        );
    }
}
