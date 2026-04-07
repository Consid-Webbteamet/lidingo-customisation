<?php

declare(strict_types=1);

namespace LidingoCustomisation\Archives;

use LidingoCustomisation\AcfFields\ServiceInfoArchivePageFields;
use ModularityServiceInfo\Helper\DateFormatter;
use ModularityServiceInfo\Helper\ServiceInfoStatus;
use WP_Post;

class ServiceInfoArchive
{
    private const POST_TYPE = 'service_information';
    private const TEMPLATE_SLUG = 'archive-post-type.blade.php';
    private const STATUS_QUERY_PARAMETER = 'service_info_status';

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    public function addHooks(): void
    {
        add_filter('template_include', [$this, 'useArchiveTemplate'], 9);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 25);
    }

    public function useArchiveTemplate(string $template): string
    {
        if (!$this->isServiceInfoView()) {
            return $template;
        }

        $archiveTemplate = path_join($this->viewPath, self::TEMPLATE_SLUG);

        return file_exists($archiveTemplate) ? $archiveTemplate : $template;
    }

    public function customizeViewData(array $viewData): array
    {
        if (!$this->isServiceInfoView()) {
            return $viewData;
        }

        $archivePage = $this->getArchivePage();
        $archivePageId = $archivePage instanceof WP_Post ? (int) $archivePage->ID : 0;
        $selectedStatus = $this->getSelectedStatus();

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
        $viewData['archiveLayoutHasActiveFilters'] = $selectedStatus !== null;
        $viewData['archiveLayoutYearOptions'] = [];
        $viewData['archiveLayoutSelectedYear'] = null;
        $viewData['archiveLayoutYearParameterName'] = '';
        $viewData['archiveLayoutCardMetaIcon'] = '';
        $viewData['archiveLayoutUsesDateBadge'] = false;
        $viewData['filterConfig'] = $viewData['filterConfig'] ?? null;
        $viewData['id'] = $viewData['id'] ?? 'service-info-archive';
        $viewData['getParentColumnClasses'] = $viewData['getParentColumnClasses'] ?? static fn(): array => ['o-grid-12'];

        $viewData['serviceInfoArchiveEnabled'] = true;
        $viewData['serviceInfoArchiveSections'] = $this->getSections($selectedStatus);
        $viewData['serviceInfoArchiveExternalSectionTitle'] = $this->getExternalSectionTitle($archivePage);
        $viewData['serviceInfoArchiveExternalItems'] = $selectedStatus === null
            ? $this->getExternalItems($archivePage)
            : [];

        return $viewData;
    }

    private function getSections(?string $selectedStatus = null): array
    {
        $sections = [
            ServiceInfoStatus::STATUS_CURRENT => [
                'title' => __('Aktuella driftstörningar', 'lidingo-customisation'),
                'emptyText' => __('Det finns inga aktuella driftstörningar just nu.', 'lidingo-customisation'),
                'items' => $this->getItemsForSection(ServiceInfoStatus::STATUS_CURRENT),
            ],
            ServiceInfoStatus::STATUS_PLANNED => [
                'title' => __('Planerade driftstörningar', 'lidingo-customisation'),
                'emptyText' => __('Det finns inga planerade driftstörningar just nu.', 'lidingo-customisation'),
                'items' => $this->getItemsForSection(ServiceInfoStatus::STATUS_PLANNED),
            ],
        ];

        if ($selectedStatus !== null) {
            return isset($sections[$selectedStatus]) ? [$sections[$selectedStatus]] : [];
        }

        return array_values($sections);
    }

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

    private function formatPost(WP_Post $post): array
    {
        $terms = get_the_terms($post->ID, 'service_category');
        $firstTerm = is_array($terms) ? reset($terms) : false;
        $icon = is_object($firstTerm) ? get_field('icon', 'service_category_' . $firstTerm->term_id) : '';

        return [
            'title' => get_the_title($post->ID),
            'link' => get_permalink($post->ID),
            'formattedDate' => DateFormatter::formatDateRange(
                (string) get_field('start_date', $post->ID),
                (string) get_field('end_date', $post->ID)
            ),
            'icon' => is_string($icon) ? $icon : '',
        ];
    }

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
            $icon = isset($item['icon']) && is_string($item['icon']) ? $item['icon'] : '';

            return [
                'title' => $title,
                'description' => $description,
                'link' => $url,
                'icon' => $icon,
            ];
        }, $items)));
    }

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

    private function getSelectedStatus(): ?string
    {
        $value = $_GET[self::STATUS_QUERY_PARAMETER] ?? '';

        if (!is_string($value) || $value === '') {
            return null;
        }

        return match (sanitize_key((string) wp_unslash($value))) {
            'current', 'ongoing', 'pagaende' => ServiceInfoStatus::STATUS_CURRENT,
            'planned', 'planerade' => ServiceInfoStatus::STATUS_PLANNED,
            default => null,
        };
    }

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

    private function getArchivePageId(): int
    {
        $pageId = (int) get_option('page_for_' . self::POST_TYPE);

        if ($pageId <= 0) {
            $pageId = (int) get_field('service_information_page', 'service-information-settings');
        }

        return $pageId > 0 ? $pageId : 0;
    }

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

    private function getArchiveContent(?WP_Post $page): string
    {
        if (!$page instanceof WP_Post || $page->post_content === '') {
            return '';
        }

        return (string) apply_filters('the_content', $page->post_content);
    }

    private function shouldDisplayArchiveHeroImage(?WP_Post $page): bool
    {
        if (!$page instanceof WP_Post) {
            return true;
        }

        if (function_exists('get_field')) {
            return (bool) get_field('post_single_show_featured_image', $page->ID);
        }

        return (bool) get_post_meta($page->ID, 'post_single_show_featured_image', true);
    }

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
