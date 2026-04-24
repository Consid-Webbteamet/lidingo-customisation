<?php

declare(strict_types=1);

namespace LidingoCustomisation\Archives;

use LidingoCustomisation\AcfFields\ServiceInfoArchivePageFields;
use LidingoCustomisation\Helper\ServiceInfoStatus;
use ModularityServiceInfo\Helper\DateFormatter;
use WP_Post;

class ServiceInfoArchive
{
    private const POST_TYPE = 'service_information';
    private const TEMPLATE_SLUG = 'archive-post-type.blade.php';

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    /** Register service info archive hooks. */
    public function addHooks(): void
    {
        add_filter('template_include', [$this, 'useArchiveTemplate'], 9);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 25);
    }

    /** Swap in the custom archive template. */
    public function useArchiveTemplate(string $template): string
    {
        if (!$this->isServiceInfoView()) {
            return $template;
        }

        $archiveTemplate = path_join($this->viewPath, self::TEMPLATE_SLUG);

        return file_exists($archiveTemplate) ? $archiveTemplate : $template;
    }

    /** Build service info archive view data. */
    public function customizeViewData(array $viewData): array
    {
        if (!$this->isServiceInfoView()) {
            return $viewData;
        }

        $archivePage = $this->getArchivePage();
        $archivePageId = $archivePage instanceof WP_Post ? (int) $archivePage->ID : 0;
        if ($archivePage instanceof WP_Post) {
            $viewData['archiveLayoutTitle'] = $this->getArchiveTitle($archivePage, $viewData);
            $viewData['archiveLayoutLead'] = $this->getArchiveLead($archivePage, $viewData);
            $viewData['archiveLayoutContent'] = $this->getArchiveContent($archivePage);
            $viewData['archiveLayoutImageHtml'] = $this->shouldDisplayArchiveHeroImage($archivePage)
                ? $this->getArchiveImageHtml($archivePage)
                : '';
            $viewData['breadcrumbMenu'] = $this->getBreadcrumbMenu($viewData, $archivePageId);
        }

        $viewData['showSidebars'] = false;
        $viewData['hasSideMenu'] = false;
        $viewData['helperNavBeforeContent'] = false;
        $viewData['skipToMainContentLink'] = '#main-content';
        $viewData['archiveLayoutPostType'] = self::POST_TYPE;
        $viewData['archiveLayoutPageId'] = $archivePageId;
        $viewData['archiveLayoutResetUrl'] = $archivePage instanceof WP_Post
            ? (string) get_permalink($archivePage)
            : home_url('/');
        $viewData['archiveLayoutHasActiveFilters'] = false;
        $viewData['archiveLayoutYearOptions'] = [];
        $viewData['archiveLayoutSelectedYear'] = null;
        $viewData['archiveLayoutYearParameterName'] = '';
        $viewData['archiveLayoutCardMetaIcon'] = '';
        $viewData['archiveLayoutUsesDateBadge'] = false;
        $viewData['archiveLayoutBadgeTaxonomy'] = '';
        $viewData['filterConfig'] = $viewData['filterConfig'] ?? null;
        $viewData['id'] = $viewData['id'] ?? 'service-info-archive';
        $getParentColumnClasses = $viewData['getParentColumnClasses'] ?? null;
        $viewData['getParentColumnClasses'] = static function () use ($getParentColumnClasses): array {
            $classes = ['o-grid-12'];

            if (is_callable($getParentColumnClasses)) {
                $resolvedClasses = $getParentColumnClasses();

                if (is_array($resolvedClasses) && !empty($resolvedClasses)) {
                    $classes = $resolvedClasses;
                }
            }

            $classes = array_values(array_filter(
                $classes,
                static fn($class): bool => is_string($class) && trim($class) !== '' && $class !== 'u-margin__bottom--12'
            ));

            return !empty($classes) ? $classes : ['o-grid-12'];
        };

        $viewData['serviceInfoArchiveEnabled'] = true;
        $viewData['serviceInfoArchiveSections'] = $this->getSections();
        $viewData['serviceInfoArchiveExternalSectionTitle'] = $this->getExternalSectionTitle($archivePage);
        $viewData['serviceInfoArchiveExternalItems'] = $this->getExternalItems($archivePage);
        $viewData['getArchiveCardBadgeLabel'] = static fn($post): string => '';

        return $viewData;
    }

    /** Build the static section list for current and planned items. */
    private function getSections(): array
    {
        return [
            [
                'title' => __('Aktuella driftstörningar', 'lidingo-customisation'),
                'emptyText' => __('Det finns inga aktuella driftstörningar just nu.', 'lidingo-customisation'),
                'items' => $this->getItemsForSection(ServiceInfoStatus::STATUS_CURRENT),
            ],
            [
                'title' => __('Planerade driftstörningar', 'lidingo-customisation'),
                'emptyText' => __('Det finns inga planerade driftstörningar just nu.', 'lidingo-customisation'),
                'items' => $this->getItemsForSection(ServiceInfoStatus::STATUS_PLANNED),
            ],
        ];
    }

    /** Query and format posts for a single archive section. */
    private function getItemsForSection(string $status): array
    {
        $query = new \WP_Query($this->getQueryArgumentsForStatus($status));

        if (!$query->have_posts()) {
            return [];
        }

        $items = array_values(array_filter(array_map(
            fn($post) => $post instanceof WP_Post ? $this->formatPost($post) : null,
            $query->posts
        )));

        wp_reset_postdata();

        return $items;
    }

    /** Build the query arguments for a specific service info status. */
    private function getQueryArgumentsForStatus(string $status): array
    {
        $metaKey = 'start_date';
        $order = 'ASC';

        if ($status === ServiceInfoStatus::STATUS_COMPLETED) {
            $metaKey = 'end_date';
            $order = 'DESC';
        }

        $metaQuery = match ($status) {
            ServiceInfoStatus::STATUS_PLANNED => ServiceInfoStatus::getPlannedMetaQuery(),
            ServiceInfoStatus::STATUS_COMPLETED => ServiceInfoStatus::getCompletedMetaQuery(),
            default => ServiceInfoStatus::getCurrentMetaQuery(),
        };

        return [
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => $metaKey,
            'orderby' => 'meta_value',
            'order' => $order,
            'meta_query' => $metaQuery,
            'no_found_rows' => true,
        ];
    }

    /** Format a service info post for the archive card list. */
    private function formatPost(WP_Post $post): array
    {
        $terms = get_the_terms($post->ID, 'service_category');
        $firstTerm = is_array($terms) ? reset($terms) : false;
        $iconAttachmentId = is_object($firstTerm)
            ? $this->normalizeAttachmentId(get_field('icon', 'service_category_' . $firstTerm->term_id))
            : 0;

        return [
            'title' => get_the_title($post->ID),
            'link' => get_permalink($post->ID),
            'formattedDate' => DateFormatter::formatDateRange(
                (string) get_field('start_date', $post->ID),
                (string) get_field('end_date', $post->ID)
            ),
            'iconImageHtml' => $this->getIconImageHtml($iconAttachmentId),
        ];
    }

    /** Map configured external links into renderable archive items. */
    private function getExternalItems(?WP_Post $archivePage): array
    {
        if (!$archivePage instanceof WP_Post) {
            return [];
        }

        $items = get_field(ServiceInfoArchivePageFields::FIELD_NAME_EXTERNAL_ITEMS, $archivePage->ID);

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item): ?array {
            if (!is_array($item)) {
                return null;
            }

            $title = isset($item['title']) && is_string($item['title']) ? trim($item['title']) : '';
            $url = isset($item['url']) && is_string($item['url']) ? trim($item['url']) : '';

            if ($title === '' || $url === '') {
                return null;
            }

            $description = isset($item['description']) && is_string($item['description']) ? trim($item['description']) : '';
            $iconAttachmentId = $this->normalizeAttachmentId($item['icon'] ?? null);

            return [
                'title' => $title,
                'description' => $description,
                'link' => $url,
                'iconImageHtml' => $this->getIconImageHtml($iconAttachmentId),
            ];
        }, $items)));
    }

    /** Normalize ACF image values to a positive attachment ID. */
    private function normalizeAttachmentId(mixed $value): int
    {
        if (is_numeric($value)) {
            $attachmentId = (int) $value;

            return $attachmentId > 0 ? $attachmentId : 0;
        }

        if (is_array($value) && isset($value['ID']) && is_numeric($value['ID'])) {
            $attachmentId = (int) $value['ID'];

            return $attachmentId > 0 ? $attachmentId : 0;
        }

        if (is_object($value) && isset($value->ID) && is_numeric($value->ID)) {
            $attachmentId = (int) $value->ID;

            return $attachmentId > 0 ? $attachmentId : 0;
        }

        return 0;
    }

    /** Resolve the archive card icon image when one is configured. */
    private function getIconImageHtml(int $attachmentId): string
    {
        if ($attachmentId <= 0) {
            return '';
        }

        $imageHtml = wp_get_attachment_image(
            $attachmentId,
            'thumbnail',
            false,
            [
                'class' => 'c-service-info-archive__icon-image',
                'alt' => '',
                'loading' => 'lazy',
                'decoding' => 'async',
            ]
        );

        return is_string($imageHtml) ? $imageHtml : '';
    }

    /** Use the custom external section title when one is configured. */
    private function getExternalSectionTitle(?WP_Post $archivePage): string
    {
        if ($archivePage instanceof WP_Post) {
            $title = get_field(ServiceInfoArchivePageFields::FIELD_NAME_EXTERNAL_SECTION_TITLE, $archivePage->ID);

            if (is_string($title) && trim($title) !== '') {
                return trim($title);
            }
        }

        return __('Driftstörningar i system som sköts av andra aktörer', 'lidingo-customisation');
    }

    /** Resolve the archive page from settings or the current page context. */
    private function getArchivePage(): ?WP_Post
    {
        $pageId = $this->getArchivePageId();

        if ($pageId <= 0 && is_page()) {
            $queriedObjectId = get_queried_object_id();
            $pageId = is_int($queriedObjectId) ? $queriedObjectId : 0;
        }
 
        if ($pageId <= 0) {
            return null;
        }

        $page = get_post($pageId);

        return $page instanceof WP_Post && $page->post_type === 'page' ? $page : null;
    }

    /** Read the archive page ID from the configured options. */
    private function getArchivePageId(): int
    {
        $pageId = (int) get_option('page_for_' . self::POST_TYPE);

        if ($pageId <= 0) {
            $pageId = (int) get_field('service_information_page', 'service-information-settings');
        }

        return $pageId > 0 ? $pageId : 0;
    }

    /** Resolve the archive title from the assigned page or view data. */
    private function getArchiveTitle(?WP_Post $page, array $viewData): string
    {
        if ($page instanceof WP_Post) {
            $title = get_the_title($page);

            if (is_string($title) && $title !== '') {
                return $title;
            }
        }

        return !empty($viewData['archiveLayoutTitle']) && is_string($viewData['archiveLayoutTitle'])
            ? $viewData['archiveLayoutTitle']
            : '';
    }

    /** Resolve the archive lead from the assigned page or view data. */
    private function getArchiveLead(?WP_Post $page, array $viewData): string
    {
        if ($page instanceof WP_Post) {
            $excerpt = get_post_field('post_excerpt', $page);

            if (is_string($excerpt) && $excerpt !== '') {
                return (string) apply_filters('the_excerpt', $excerpt);
            }
        }

        return !empty($viewData['archiveLayoutLead']) && is_string($viewData['archiveLayoutLead'])
            ? $viewData['archiveLayoutLead']
            : '';
    }

    /** Return the assigned archive page content when available. */
    private function getArchiveContent(?WP_Post $page): string
    {
        if (!$page instanceof WP_Post || $page->post_content === '') {
            return '';
        }

        return (string) apply_filters('the_content', $page->post_content);
    }

    /** Respect the archive page featured image toggle when it has been set. */
    private function shouldDisplayArchiveHeroImage(?WP_Post $page): bool
    {
        if (!$page instanceof WP_Post) {
            return true;
        }

        $thumbnailId = get_post_thumbnail_id($page);
        $hasFeaturedImage = is_numeric($thumbnailId) && (int) $thumbnailId > 0;

        if (metadata_exists('post', $page->ID, 'post_single_show_featured_image')) {
            return (bool) get_post_meta($page->ID, 'post_single_show_featured_image', true);
        }

        return $hasFeaturedImage;
    }

    /** Render the archive page featured image when present. */
    private function getArchiveImageHtml(?WP_Post $page): string
    {
        if (!$page instanceof WP_Post) {
            return '';
        }

        $thumbnailId = get_post_thumbnail_id($page);

        if (!is_numeric($thumbnailId) || (int) $thumbnailId <= 0) {
            return '';
        }

        $imageHtml = wp_get_attachment_image(
            (int) $thumbnailId,
            'large',
            false,
            [
                'class' => 'c-post-type-archive__hero-image',
                'loading' => 'eager',
                'decoding' => 'async',
            ]
        );

        return is_string($imageHtml) ? $imageHtml : '';
    }

    /** Build the archive breadcrumb trail from the assigned page. */
    private function getBreadcrumbMenu(array $viewData, int $pageId): array
    {
        $breadcrumbMenu = is_array($viewData['breadcrumbMenu'] ?? null)
            ? $viewData['breadcrumbMenu']
            : [];

        if ($pageId <= 0) {
            return $breadcrumbMenu;
        }

        $existingItems = array_values(is_array($breadcrumbMenu['items'] ?? null) ? $breadcrumbMenu['items'] : []);
        $items = [];

        if (!empty($existingItems[0]) && is_array($existingItems[0])) {
            $items[] = $existingItems[0];
        } else {
            $items[] = [
                'label' => __('Home', 'municipio'),
                'href' => home_url('/'),
                'current' => false,
                'icon' => 'home',
            ];
        }

        $frontPageId = (int) get_option('page_on_front');
        $ancestorIds = array_reverse(array_filter(array_map('intval', get_post_ancestors($pageId))));

        foreach ($ancestorIds as $ancestorId) {
            if ($ancestorId <= 0 || $ancestorId === $frontPageId) {
                continue;
            }

            $items[] = [
                'label' => get_the_title($ancestorId) ?: __('Untitled page', 'municipio'),
                'href' => get_permalink($ancestorId),
                'current' => false,
                'icon' => 'chevron_right',
            ];
        }

        $items[] = [
            'label' => get_the_title($pageId) ?: __('Untitled page', 'municipio'),
            'href' => get_permalink($pageId),
            'current' => true,
            'icon' => 'chevron_right',
        ];

        $breadcrumbMenu['items'] = $items;

        return $breadcrumbMenu;
    }

    /** Detect the service info archive view on the archive page or configured page. */
    private function isServiceInfoView(): bool
    {
        if (is_post_type_archive(self::POST_TYPE)) {
            return true;
        }

        if (!is_page()) {
            return false;
        }

        $archivePageId = $this->getArchivePageId();
        $queriedObjectId = get_queried_object_id();

        return $archivePageId > 0 && is_int($queriedObjectId) && $queriedObjectId === $archivePageId;
    }
}
