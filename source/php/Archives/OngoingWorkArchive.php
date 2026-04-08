<?php

declare(strict_types=1);

namespace LidingoCustomisation\Archives;

use DateTimeImmutable;
use DateTimeZone;
use Municipio\PostObject\PostObjectInterface;
use WP_Post;
use WP_Query;

class OngoingWorkArchive
{
    private const POST_TYPE = 'pagaende-arbeten';
    private const START_DATE_META_KEY = 'ongoing_work_start_date';
    private const END_DATE_META_KEY = 'ongoing_work_end_date';
    private const YEAR_QUERY_PARAMETER = 'ongoing_work_year';
    private const YEAR_OPTIONS_TRANSIENT = 'lidingo_ongoing_work_year_options';

    /** Register ongoing work archive hooks. */
    public function addHooks(): void
    {
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 20);
        add_action('pre_get_posts', [$this, 'filterArchivePostsByYear']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'clearYearOptionsCache']);
        add_action('deleted_post', [$this, 'clearYearOptionsCacheOnDelete']);
    }

    /** Build the archive view data and year filter state. */
    public function customizeViewData(array $viewData): array
    {
        if (!$this->isOngoingWorkArchive()) {
            return $viewData;
        }

        $page = $this->getArchivePage();
        $pageId = $page?->ID;

        $viewData['showSidebars'] = false;
        $viewData['hasSideMenu'] = false;
        $viewData['helperNavBeforeContent'] = false;
        $viewData['skipToMainContentLink'] = '#main-content';
        $viewData['breadcrumbMenu'] = $this->getBreadcrumbMenu($viewData, $pageId);
        $viewData['archiveLayoutTitle'] = $this->getArchiveTitle($page, $viewData);
        $viewData['archiveLayoutLead'] = $this->getArchiveLead($page, $viewData);
        $viewData['archiveLayoutContent'] = $this->getArchiveContent($page);
        $viewData['archiveLayoutImageHtml'] = $this->shouldDisplayArchiveHeroImage($page)
            ? $this->getArchiveImageHtml($page)
            : '';
        $viewData['archiveLayoutYearOptions'] = $this->getYearOptions();
        $viewData['archiveLayoutSelectedYear'] = $this->getSelectedYear();
        $viewData['archiveLayoutYearParameterName'] = self::YEAR_QUERY_PARAMETER;
        $viewData['archiveLayoutResetUrl'] = $this->getArchiveResetUrl($page);
        $viewData['archiveLayoutHasActiveFilters'] = $this->hasActiveFilters($viewData['filterConfig'] ?? null);
        $viewData['archiveLayoutCardMetaIcon'] = ':kalender:';
        $viewData['getArchiveCardMeta'] = fn(PostObjectInterface $post): string => $this->getDateRangeLabel($post);

        return $viewData;
    }

    /** Filter archive queries by selected year. */
    public function filterArchivePostsByYear(WP_Query $query): void
    {
        if (
            is_admin()
            || !$query instanceof WP_Query
            || !$this->isArchiveQueryForOngoingWork($query)
        ) {
            return;
        }

        if ((bool) $query->get('lidingo_skip_ongoing_work_year_filter')) {
            return;
        }

        if (!$this->queryTargetsOngoingWorkPosts($query)) {
            return;
        }

        $selectedYear = $this->getSelectedYear();

        if ($selectedYear === null) {
            return;
        }

        $yearStart = (int) sprintf('%d0101', $selectedYear);
        $yearEnd = (int) sprintf('%d1231', $selectedYear);
        $metaQuery = $query->get('meta_query');
        $metaQuery = is_array($metaQuery) ? $metaQuery : [];

        $metaQuery[] = [
            'key' => self::START_DATE_META_KEY,
            'value' => $yearEnd,
            'compare' => '<=',
            'type' => 'NUMERIC',
        ];

        $metaQuery[] = [
            'relation' => 'OR',
            [
                'key' => self::END_DATE_META_KEY,
                'value' => $yearStart,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ],
            [
                'key' => self::END_DATE_META_KEY,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => self::END_DATE_META_KEY,
                'value' => '',
                'compare' => '=',
            ],
        ];

        $query->set('meta_query', $metaQuery);
    }

    /** Clear cached year options after save. */
    public function clearYearOptionsCache(): void
    {
        delete_transient($this->getYearOptionsTransientKey());
    }

    /** Clear cached year options after delete. */
    public function clearYearOptionsCacheOnDelete(int $postId): void
    {
        if (get_post_type($postId) !== self::POST_TYPE) {
            return;
        }

        $this->clearYearOptionsCache();
    }

    private function isOngoingWorkArchive(): bool
    {
        return is_post_type_archive(self::POST_TYPE);
    }

    private function getArchivePage(): ?WP_Post
    {
        $pageId = get_option('page_for_' . self::POST_TYPE);

        if (!is_numeric($pageId) || (int) $pageId <= 0) {
            return null;
        }

        $page = get_post((int) $pageId);

        return $page instanceof WP_Post && $page->post_type === 'page'
            ? $page
            : null;
    }

    private function getArchiveTitle(?WP_Post $page, array $viewData): string
    {
        if ($page instanceof WP_Post) {
            $title = get_the_title($page);

            if (is_string($title) && $title !== '') {
                return $title;
            }
        }

        if (!empty($viewData['archiveTitle']) && is_string($viewData['archiveTitle'])) {
            return $viewData['archiveTitle'];
        }

        $postTypeObject = get_post_type_object(self::POST_TYPE);

        return is_object($postTypeObject) && !empty($postTypeObject->labels->name)
            ? (string) $postTypeObject->labels->name
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

        if (!empty($viewData['archiveLead']) && is_string($viewData['archiveLead'])) {
            return wpautop($viewData['archiveLead']);
        }

        return '';
    }

    private function getArchiveContent(?WP_Post $page): string
    {
        if (!$page instanceof WP_Post) {
            return '';
        }

        $content = (string) $page->post_content;

        if ($content === '') {
            return '';
        }

        return (string) apply_filters('the_content', $content);
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

    private function getBreadcrumbMenu(array $viewData, ?int $pageId): array
    {
        $breadcrumbMenu = is_array($viewData['breadcrumbMenu'] ?? null)
            ? $viewData['breadcrumbMenu']
            : [];

        if (!$pageId || $pageId <= 0) {
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

    private function getSelectedYear(): ?int
    {
        $year = isset($_GET[self::YEAR_QUERY_PARAMETER]) ? sanitize_text_field((string) $_GET[self::YEAR_QUERY_PARAMETER]) : '';

        if (!preg_match('/^\d{4}$/', $year)) {
            return null;
        }

        $year = (int) $year;

        return $year >= 2000 && $year <= 2100 ? $year : null;
    }

    private function getArchiveResetUrl(?WP_Post $page): string
    {
        if ($page instanceof WP_Post) {
            $pageUrl = get_permalink($page);

            if (is_string($pageUrl) && $pageUrl !== '') {
                return $pageUrl;
            }
        }

        $archiveUrl = get_post_type_archive_link(self::POST_TYPE);

        return is_string($archiveUrl) ? $archiveUrl : home_url('/');
    }

    private function hasActiveFilters(mixed $filterConfig): bool
    {
        if ($this->getSelectedYear() !== null) {
            return true;
        }

        return is_object($filterConfig) && method_exists($filterConfig, 'getResetUrl') && !empty($filterConfig->getResetUrl());
    }

    private function getYearOptions(): array
    {
        $cachedYears = get_transient($this->getYearOptionsTransientKey());

        if (is_array($cachedYears)) {
            return $cachedYears;
        }

        $postIds = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'lidingo_skip_ongoing_work_year_filter' => true,
        ]);

        if (empty($postIds) || !is_array($postIds)) {
            return [];
        }

        $years = [];

        foreach ($postIds as $postId) {
            $startDate = $this->getPostDateValue((int) $postId, self::START_DATE_META_KEY);

            if (!$startDate instanceof DateTimeImmutable) {
                continue;
            }

            $endDate = $this->getPostDateValue((int) $postId, self::END_DATE_META_KEY);
            $startYear = (int) $startDate->format('Y');
            $endYear = $endDate instanceof DateTimeImmutable ? (int) $endDate->format('Y') : $startYear;

            if ($endYear < $startYear) {
                $endYear = $startYear;
            }

            foreach (range($startYear, $endYear) as $year) {
                $years[$year] = (string) $year;
            }
        }

        krsort($years, SORT_NUMERIC);
        set_transient($this->getYearOptionsTransientKey(), $years, DAY_IN_SECONDS);

        return $years;
    }

    private function getDateRangeLabel(PostObjectInterface $post): string
    {
        $postId = $post->getId();

        if ($postId <= 0) {
            return '';
        }

        $startDate = $this->getPostDateValue($postId, self::START_DATE_META_KEY);

        if (!$startDate instanceof DateTimeImmutable) {
            return '';
        }

        $endDate = $this->getPostDateValue($postId, self::END_DATE_META_KEY);

        if ($endDate instanceof DateTimeImmutable && $endDate < $startDate) {
            $endDate = null;
        }

        $startLabel = $this->capitalizeFirstLetter($this->formatMonthYear($startDate));

        if (!$endDate instanceof DateTimeImmutable || $startDate->format('Ym') === $endDate->format('Ym')) {
            return $startLabel;
        }

        return sprintf(
            '%s - %s',
            $startLabel,
            $this->formatMonthYear($endDate)
        );
    }

    private function queryTargetsOngoingWorkPosts(WP_Query $query): bool
    {
        $postType = $query->get('post_type');

        if (is_array($postType)) {
            return in_array(self::POST_TYPE, $postType, true);
        }

        return $postType === self::POST_TYPE;
    }

    private function isArchiveQueryForOngoingWork(WP_Query $query): bool
    {
        if ((bool) $query->is_post_type_archive(self::POST_TYPE)) {
            return true;
        }

        return $this->queryTargetsOngoingWorkPosts($query);
    }

    private function getPostDateValue(int $postId, string $metaKey): ?DateTimeImmutable
    {
        $rawValue = get_post_meta($postId, $metaKey, true);

        if (!is_string($rawValue) || $rawValue === '') {
            return null;
        }

        return $this->parseDateValue($rawValue);
    }

    private function parseDateValue(string $value): ?DateTimeImmutable
    {
        $timezone = wp_timezone();

        if (!$timezone instanceof DateTimeZone) {
            $timezone = new DateTimeZone('Europe/Stockholm');
        }

        $formats = ['!Ymd', '!Y-m-d'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);

            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        return null;
    }

    private function formatMonthYear(DateTimeImmutable $date): string
    {
        return wp_date('F Y', $date->getTimestamp(), $date->getTimezone());
    }

    private function capitalizeFirstLetter(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($value, 0, 1), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
        }

        return ucfirst($value);
    }

    private function getYearOptionsTransientKey(): string
    {
        return self::YEAR_OPTIONS_TRANSIENT . '_' . get_current_blog_id();
    }
}
