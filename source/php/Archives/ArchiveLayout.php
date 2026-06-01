<?php

declare(strict_types=1);

namespace LidingoCustomisation\Archives;

use LidingoCustomisation\AcfFields\ArchivePageFields;
use Municipio\Helper\DateFormat;
use Municipio\PostObject\PostObjectInterface;
use WP_Post;
use WP_Post_Type;
use WP_Query;
use WP_Admin_Bar;

class ArchiveLayout
{
    public const TEMPLATE_SLUG = 'archive-post-type.blade.php';
    public const BADGE_TAXONOMY_FIELD_NAME = 'lidingo_archive_badge_taxonomy';
    private const EVENT_ARCHIVE_POST_TYPE = 'evenemang';
    private const POSTS_LIST_DATE_CLAUSE_KEY = 'date_clause';
    private const DATE_BADGE_POST_TYPES = ['news', 'nyheter'];
    private const HERO_IMAGE_EXCLUDED_POST_TYPES = ['evenemang'];
    private const SWEDISH_TITLE_COLLATION = 'utf8mb4_swedish_ci';

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    /** Register archive layout hooks. */
    public function addHooks(): void
    {
        add_action('init', [$this, 'registerArchiveOrderDirectionFilters'], 20);
        add_action('admin_bar_menu', [$this, 'addArchivePageEditLink'], 80);
        add_action('pre_get_posts', [$this, 'filterHierarchicalArchivePosts']);
        add_action('pre_get_posts', [$this, 'removeForcedCurrentDateEventFilter'], 50);
        add_filter('posts_orderby', [$this, 'applySwedishTitleSorting'], 20, 2);
        add_filter('body_class', [$this, 'filterBodyClasses'], 20);
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('document_title_parts', [$this, 'filterDocumentTitleParts'], 20);
        add_filter('pre_get_document_title', [$this, 'filterDocumentTitle'], 20);
        add_filter('Municipio/postTitle', [$this, 'filterMunicipioPostTitle'], 20);
        add_filter('template_include', [$this, 'useArchiveTemplate'], 9);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 15);
        add_filter('Municipio/Archive/TaxonomyFilter/Label', [$this, 'filterArchiveTaxonomyFilterLabel'], 10, 2);
        add_filter('Municipio/Archive/TaxonomyFilter/Placeholder', [$this, 'filterArchiveTaxonomyFilterLabel'], 10, 2);
    }

    /** Restore the archive page edit link when the theme falls back to child pages. */
    public function addArchivePageEditLink(WP_Admin_Bar $adminBar): void
    {
        if (!$this->isFallbackArchiveView()) {
            return;
        }

        $postType = $this->getCurrentPostType();
        if ($postType === null) {
            return;
        }

        $page = $this->getArchivePage($postType);
        if (!$page instanceof WP_Post || !current_user_can('edit_post', $page->ID)) {
            return;
        }

        $adminBar->add_node([
            'id' => 'edit',
            'title' => '<span class="ab-item"></span>' . __('Edit Page', 'wp-page-for-post-type'),
            'href' => (string) get_edit_post_link($page->ID),
        ]);
    }

    /** Keep the document title aligned with the assigned archive page in fallback mode. */
    public function filterDocumentTitleParts(array $parts): array
    {
        if (!$this->isFallbackArchiveView()) {
            return $parts;
        }

        $postType = $this->getCurrentPostType();
        if ($postType === null) {
            return $parts;
        }

        $page = $this->getArchivePage($postType);
        if (!$page instanceof WP_Post) {
            return $parts;
        }

        $title = get_the_title($page);
        if (is_string($title) && $title !== '') {
            $parts['title'] = $title;
        }

        return $parts;
    }

    /** Restore the expected archive body classes when the theme swaps the query to pages. */
    public function filterBodyClasses(array $classes): array
    {
        if (!$this->isFallbackArchiveView()) {
            return $classes;
        }

        $postType = $this->getCurrentPostType();
        if ($postType === null) {
            return $classes;
        }

        $classes = array_values(array_filter(
            $classes,
            static fn($class): bool => !in_array($class, ['post-type-archive-page'], true)
        ));

        $classes[] = 'post-type-archive-' . $postType;

        return array_values(array_unique($classes));
    }

    /** Override the rendered document title in fallback archive mode. */
    public function filterDocumentTitle(string $title): string
    {
        if (!$this->isFallbackArchiveView()) {
            return $title;
        }

        $postType = $this->getCurrentPostType();
        if ($postType === null) {
            return $title;
        }

        $page = $this->getArchivePage($postType);
        if (!$page instanceof WP_Post) {
            return $title;
        }

        $pageTitle = get_the_title($page);

        return is_string($pageTitle) && $pageTitle !== '' ? $pageTitle : $title;
    }

    /** Override Municipio's rendered page title in fallback archive mode. */
    public function filterMunicipioPostTitle(string $title): string
    {
        if (!$this->isFallbackArchiveView()) {
            return $title;
        }

        return $this->filterDocumentTitle($title);
    }

    /** Limit hierarchical post type archives to top-level posts only. */
    public function filterHierarchicalArchivePosts(WP_Query $query): void
    {
        if (
            is_admin()
            || !is_post_type_archive()
        ) {
            return;
        }

        if ($this->isExplicitPostLookupQuery($query)) {
            return;
        }

        $postType = $this->resolveArchivePostType($query);
        if ($postType === null) {
            return;
        }

        $postTypeObject = get_post_type_object($postType);

        if (
            !$postTypeObject instanceof WP_Post_Type
            || empty($postTypeObject->hierarchical)
            || !in_array($postType, $this->getSupportedPostTypes(), true)
        ) {
            return;
        }

        $query->set('post_parent', 0);
    }

    /** Let archive date filters include past events by removing Municipio's extra future-only clause. */
    public function removeForcedCurrentDateEventFilter(WP_Query $query): void
    {
        if (is_admin() || !$this->isCurrentEventArchiveView()) {
            return;
        }

        $postType = $query->get('post_type');

        if (is_array($postType)) {
            $postType = end($postType);
        }

        if ($postType !== self::EVENT_ARCHIVE_POST_TYPE) {
            return;
        }

        $metaQuery = $query->get('meta_query');

        if (!is_array($metaQuery) || empty($metaQuery)) {
            return;
        }

        if (!$this->hasPostsListDateClause($metaQuery)) {
            return;
        }

        $query->set('meta_query', $this->withoutForcedCurrentDateClause($metaQuery));
    }

    /** Leave explicitly targeted queries alone and only constrain archive-style lists. */
    private function isExplicitPostLookupQuery(WP_Query $query): bool
    {
        foreach (['p', 'page_id', 'pagename', 'name', 'post_name__in', 'post__in'] as $queryVar) {
            $value = $query->get($queryVar);

            if (is_array($value) && !empty($value)) {
                return true;
            }

            if (!is_array($value) && $value !== null && $value !== '' && $value !== 0 && $value !== '0') {
                return true;
            }
        }

        $postParent = $query->get('post_parent');

        return $postParent !== null && $postParent !== '';
    }

    /** Resolve the archive post type even when the query var is absent from custom rewrites. */
    private function resolveArchivePostType(WP_Query $query): ?string
    {
        $postType = $query->get('post_type');

        if (is_array($postType)) {
            $postType = reset($postType);
        }

        if (is_string($postType) && $postType !== '') {
            return sanitize_key($postType);
        }

        $currentPostType = $this->getCurrentPostType();
        if (is_string($currentPostType) && $currentPostType !== '') {
            return $currentPostType;
        }

        foreach ($this->getSupportedPostTypes() as $supportedPostType) {
            if ((bool) $query->is_post_type_archive($supportedPostType)) {
                return $supportedPostType;
            }
        }

        return null;
    }

    /** Register archive order direction filters. */
    public function registerArchiveOrderDirectionFilters(): void
    {
        foreach ($this->getSupportedPostTypes() as $postType) {
            add_filter(
                'theme_mod_archive_' . $postType . '_order_direction',
                fn($value) => $this->normalizeArchiveOrderDirection($value, $postType)
            );
        }
    }

    /** Add the package-local archive view path. */
    public function addViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            $viewPaths[] = $this->viewPath;
        }

        return $viewPaths;
    }

    /** Swap in the custom archive template. */
    public function useArchiveTemplate(string $template): string
    {
        if (!$this->shouldUseArchiveLayout()) {
            return $template;
        }

        $archiveTemplate = path_join($this->viewPath, self::TEMPLATE_SLUG);

        return file_exists($archiveTemplate) ? $archiveTemplate : $template;
    }

    /** Prepare archive layout view data. */
    public function customizeViewData(array $viewData): array
    {
        if (!$this->shouldUseArchiveLayout()) {
            return $viewData;
        }

        $postType = $this->getCurrentPostType();

        if ($postType === null) {
            return $viewData;
        }

        if ($this->isFallbackArchiveView()) {
            $viewData['posts'] = [];
            $viewData['paginationEnabled'] = static fn(): bool => false;
        }

        $page = $this->getArchivePage($postType);
        $pageId = $page?->ID;

        $viewData['showSidebars'] = false;
        $viewData['hasSideMenu'] = false;
        $viewData['helperNavBeforeContent'] = false;
        $viewData['skipToMainContentLink'] = '#main-content';
        $viewData['archiveLayoutPostType'] = $postType;
        $viewData['archiveLayoutPageId'] = $pageId;
        $viewData['archiveLayoutTitle'] = $this->getArchiveTitle($postType, $page, $viewData);
        $viewData['archiveLayoutLead'] = $this->getArchiveLead($page, $viewData);
        $viewData['archiveLayoutContent'] = $this->getArchiveContent($page);
        $viewData['archiveLayoutImageHtml'] = $this->shouldDisplayArchiveHeroImage($page, $viewData)
            ? $this->getArchiveImageHtml($page)
            : '';
        $viewData['archiveLayoutHeroAsideCard'] = $this->getArchiveHeroAsideCard($pageId, $postType);
        if (is_array($viewData['archiveLayoutHeroAsideCard'])) {
            $viewData['archiveLayoutImageHtml'] = '';
        }
        $viewData['archiveLayoutResetUrl'] = $this->getArchiveResetUrl($postType, $page);
        $viewData['archiveLayoutHasActiveFilters'] = $this->hasActiveFilters($viewData['filterConfig'] ?? null);
        $viewData['archiveLayoutYearOptions'] = [];
        $viewData['archiveLayoutSelectedYear'] = null;
        $viewData['archiveLayoutYearParameterName'] = '';
        $viewData['archiveLayoutCardMetaIcon'] = '';
        $viewData['breadcrumbMenu'] = $this->getBreadcrumbMenu($viewData, $pageId);
        $viewData['archiveLayoutUsesDateBadge'] = $this->shouldUseDateBadge($postType);

        $badgeTaxonomy = $this->getArchiveBadgeTaxonomy($pageId, $postType);
        $viewData['archiveLayoutBadgeTaxonomy'] = $badgeTaxonomy;
        $viewData['getArchiveCardBadgeLabel'] = fn(PostObjectInterface $post): string => $this->getCardBadgeLabel($post, $postType, $badgeTaxonomy);
        $viewData['getArchiveCardMeta'] = static fn(PostObjectInterface $post): string => '';

        return $viewData;
    }

    /** Shorten taxonomy filter labels that include the current archive post type label. */
    public function filterArchiveTaxonomyFilterLabel(mixed $label, mixed $taxonomy): mixed
    {
        if (!is_string($label) || $label === '' || !$this->shouldUseArchiveLayout()) {
            return $label;
        }

        $postType = $this->getCurrentPostType();

        if ($postType === null || !is_object($taxonomy) || empty($taxonomy->object_type)) {
            return $label;
        }

        $objectTypes = is_array($taxonomy->object_type) ? $taxonomy->object_type : [$taxonomy->object_type];
        $objectTypes = array_map(static fn($objectType): string => is_string($objectType) ? sanitize_key($objectType) : '', $objectTypes);

        if (!in_array($postType, $objectTypes, true)) {
            return $label;
        }

        return $this->stripPostTypeLabelSuffix($label, $postType);
    }

    /** Apply Swedish title sorting for title-sorted archive queries when supported by the database. */
    public function applySwedishTitleSorting(string $orderby, WP_Query $query): string
    {
        if (!$this->shouldApplySwedishTitleSorting($query)) {
            return $orderby;
        }

        global $wpdb;

        $order = strtoupper((string) $query->get('order')) === 'DESC' ? 'DESC' : 'ASC';

        return sprintf(
            '%1$s.post_title COLLATE %2$s %3$s, %1$s.ID %3$s',
            $wpdb->posts,
            self::SWEDISH_TITLE_COLLATION,
            $order
        );
    }

    /** Apply the custom archive layout only on supported public post type archives. */
    private function shouldUseArchiveLayout(): bool
    {
        $postType = $this->getCurrentPostType();

        return ($this->isFallbackArchiveView() || is_post_type_archive())
            && $postType !== null
            && in_array($postType, $this->getSupportedPostTypes(), true);
    }

    /** Check whether the current request is the events archive handled by this layout. */
    private function isCurrentEventArchiveView(): bool
    {
        return $this->getCurrentPostType() === self::EVENT_ARCHIVE_POST_TYPE
            && $this->shouldUseArchiveLayout();
    }

    /** Remove the current post type label from the end of a taxonomy filter label. */
    private function stripPostTypeLabelSuffix(string $label, string $postType): string
    {
        $postTypeObject = get_post_type_object($postType);

        if (!$postTypeObject instanceof WP_Post_Type) {
            return $label;
        }

        $postTypeLabels = array_filter(array_unique([
            $postTypeObject->labels->name ?? '',
            $postTypeObject->labels->singular_name ?? '',
            $postTypeObject->label ?? '',
        ]), static fn($postTypeLabel): bool => is_string($postTypeLabel) && trim($postTypeLabel) !== '');

        foreach ($postTypeLabels as $postTypeLabel) {
            $pattern = '/(?:\s*[-–—,:]\s*|\s+)' . preg_quote(trim((string) $postTypeLabel), '/') . '$/iu';
            $shortLabel = preg_replace($pattern, '', trim($label));

            if (is_string($shortLabel) && $shortLabel !== '' && $shortLabel !== trim($label)) {
                return $shortLabel;
            }
        }

        return $label;
    }

    /** Collect public archive-capable post types, plus project overrides. */
    private function getSupportedPostTypes(): array
    {
        $defaultPostTypes = array_values(array_filter(
            array_map(
                static function ($postType): string {
                    if (!is_object($postType) || empty($postType->name) || !is_string($postType->name)) {
                        return '';
                    }

                    if (in_array($postType->name, ['attachment', 'page'], true)) {
                        return '';
                    }

                    return !empty($postType->has_archive) ? sanitize_key($postType->name) : '';
                },
                get_post_types(
                    [
                        'public' => true,
                        'publicly_queryable' => true,
                    ],
                    'objects'
                )
            ),
            static fn(string $postType): bool => $postType !== ''
        ));

        $postTypes = apply_filters(
            'lidingo_customisation/archive_layout_post_types',
            $defaultPostTypes
        );

        if (!is_array($postTypes)) {
            return $defaultPostTypes;
        }

        $postTypes = array_values(array_filter(
            array_map(
                static fn($postType): string => is_string($postType) ? sanitize_key($postType) : '',
                $postTypes
            ),
            static fn(string $postType): bool => $postType !== ''
        ));

        return !empty($postTypes) ? $postTypes : $defaultPostTypes;
    }

    /** Only override archive queries that explicitly sort by title. */
    private function shouldApplySwedishTitleSorting(WP_Query $query): bool
    {
        if (
            is_admin()
            || !is_post_type_archive()
            || !$this->queryOrdersByTitle($query->get('orderby'))
        ) {
            return false;
        }

        $postType = $this->resolveArchivePostType($query);
        $currentPostType = $this->getCurrentPostType();

        return $postType !== null
            && $currentPostType !== null
            && $postType === $currentPostType
            && in_array($postType, $this->getSupportedPostTypes(), true)
            && !$this->isExplicitPostLookupQuery($query);
    }

    /** Detect whether the current query is title-sorted. */
    private function queryOrdersByTitle(mixed $orderBy): bool
    {
        if (is_string($orderBy)) {
            return in_array($orderBy, ['title', 'post_title'], true);
        }

        if (!is_array($orderBy)) {
            return false;
        }

        foreach ($orderBy as $key => $value) {
            if (in_array($key, ['title', 'post_title'], true) || in_array($value, ['title', 'post_title'], true)) {
                return true;
            }
        }

        return false;
    }

    /** Default unset title-sorted archives to ascending order. */
    private function normalizeArchiveOrderDirection(mixed $value, string $postType): mixed
    {
        if (!$this->isUnsetThemeModValue($value)) {
            return $value;
        }

        $orderBy = get_theme_mod('archive_' . $postType . '_order_by');

        return in_array($orderBy, ['title', 'post_title'], true)
            ? 'ASC'
            : $value;
    }

    /** Resolve the current archive post type from the query, queried object, or fallback archive page. */
    private function getCurrentPostType(): ?string
    {
        $queriedObject = get_queried_object();

        if ($queriedObject instanceof WP_Post_Type && !empty($queriedObject->name)) {
            $queriedPostType = sanitize_key((string) $queriedObject->name);

            if ($queriedPostType !== 'page') {
                return $queriedPostType;
            }
        }

        $query = $this->getCurrentArchiveQuery();

        if ($query instanceof WP_Query) {
            $postType = $query->query['post_type'] ?? null;

            if (is_string($postType) && $postType !== '' && $postType !== 'page') {
                return sanitize_key($postType);
            }
        }

        $fallbackPostType = $this->resolveFallbackArchivePostType();
        if ($fallbackPostType !== null) {
            return $fallbackPostType;
        }

        return null;
    }

    /** Detect whether Municipio has swapped an empty archive query to page children. */
    private function isFallbackArchiveView(): bool
    {
        return $this->resolveFallbackArchivePostType() !== null;
    }

    /** Resolve the original post type when the theme has swapped the archive query. */
    private function resolveFallbackArchivePostType(): ?string
    {
        if (!is_archive()) {
            return null;
        }

        $query = $this->getCurrentArchiveQuery();
        if (!$query instanceof WP_Query) {
            return null;
        }

        $postType = $query->get('post_type');
        if (is_array($postType)) {
            $postType = end($postType);
        }

        if ($postType !== 'page') {
            return null;
        }

        $archivePageId = (int) $query->get('child_of');
        if ($archivePageId <= 0) {
            return null;
        }

        foreach ($this->getSupportedPostTypes() as $supportedPostType) {
            if ((int) get_option('page_for_' . $supportedPostType) === $archivePageId) {
                return $supportedPostType;
            }
        }

        return null;
    }

    /** Return the current main archive query when available. */
    private function getCurrentArchiveQuery(): ?WP_Query
    {
        global $wp_query;

        return $wp_query instanceof WP_Query ? $wp_query : null;
    }

    /** Detect the date clause generated by Municipio's posts list filters. */
    private function hasPostsListDateClause(array $metaQuery): bool
    {
        return isset($metaQuery[self::POSTS_LIST_DATE_CLAUSE_KEY])
            && is_array($metaQuery[self::POSTS_LIST_DATE_CLAUSE_KEY]);
    }

    /** Remove Municipio's extra startDate >= now clause while preserving explicit archive date filters. */
    private function withoutForcedCurrentDateClause(array $metaQuery): array
    {
        foreach ($metaQuery as $key => $clause) {
            if (
                $key === self::POSTS_LIST_DATE_CLAUSE_KEY
                || !is_array($clause)
                || ($clause['key'] ?? null) !== 'startDate'
                || ($clause['compare'] ?? null) !== '>='
                || ($clause['type'] ?? null) !== 'DATETIME'
            ) {
                continue;
            }

            unset($metaQuery[$key]);
        }

        return $metaQuery;
    }

    /** Load the page assigned to the current archive post type. */
    private function getArchivePage(string $postType): ?WP_Post
    {
        $pageId = get_option('page_for_' . $postType);

        if (!is_numeric($pageId) || (int) $pageId <= 0) {
            return null;
        }

        $page = get_post((int) $pageId);

        return $page instanceof WP_Post && $page->post_type === 'page'
            ? $page
            : null;
    }

    /** Resolve the archive title from the assigned page or archive metadata. */
    private function getArchiveTitle(string $postType, ?WP_Post $page, array $viewData): string
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

        $postTypeObject = get_post_type_object($postType);

        return is_object($postTypeObject) && !empty($postTypeObject->labels->name)
            ? (string) $postTypeObject->labels->name
            : '';
    }

    /** Resolve the archive lead from the assigned page or archive metadata. */
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

    /** Return the assigned archive page content when available. */
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

    /** Respect the archive page featured image toggle except on excluded archives. */
    private function shouldDisplayArchiveHeroImage(?WP_Post $page, array $viewData): bool
    {
        $postType = $this->getCurrentPostType();

        if (is_string($postType) && in_array($postType, self::HERO_IMAGE_EXCLUDED_POST_TYPES, true)) {
            return false;
        }

        if ($page instanceof WP_Post) {
            $thumbnailId = get_post_thumbnail_id($page);
            $hasFeaturedImage = is_numeric($thumbnailId) && (int) $thumbnailId > 0;

            if (metadata_exists('post', $page->ID, 'post_single_show_featured_image')) {
                return (bool) get_post_meta($page->ID, 'post_single_show_featured_image', true);
            }

            return $hasFeaturedImage;
        }

        $appearanceConfig = $viewData['appearanceConfig'] ?? null;

        if (is_object($appearanceConfig) && method_exists($appearanceConfig, 'shouldDisplayFeaturedImage')) {
            return (bool) $appearanceConfig->shouldDisplayFeaturedImage();
        }

        $archiveProps = $viewData['archiveProps'] ?? null;

        if (is_object($archiveProps)) {
            foreach (['displayFeaturedImage', 'featured_image'] as $property) {
                if (isset($archiveProps->{$property})) {
                    return filter_var($archiveProps->{$property}, FILTER_VALIDATE_BOOL);
                }
            }
        }

        return true;
    }

    /** Merge the assigned page breadcrumb trail into the archive view data. */
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

    /** Return the archive reset URL, falling back to the post type archive. */
    private function getArchiveResetUrl(string $postType, ?WP_Post $page): string
    {
        if ($page instanceof WP_Post) {
            $pageUrl = get_permalink($page);

            if (is_string($pageUrl) && $pageUrl !== '') {
                return $pageUrl;
            }
        }

        $archiveUrl = get_post_type_archive_link($postType);

        return is_string($archiveUrl) ? $archiveUrl : home_url('/');
    }

    /** Treat a resettable filter config as an active archive filter. */
    private function hasActiveFilters(mixed $filterConfig): bool
    {
        return is_object($filterConfig) && method_exists($filterConfig, 'getResetUrl') && !empty($filterConfig->getResetUrl());
    }

    /** Read the optional event archive hero card content from the assigned archive page. */
    private function getArchiveHeroAsideCard(?int $pageId, string $postType): ?array
    {
        if ($postType !== 'evenemang' || !$pageId || $pageId <= 0 || !function_exists('get_field')) {
            return null;
        }

        $title = get_field(ArchivePageFields::FIELD_NAME_EVENT_HERO_CARD_TITLE, $pageId);
        $link = get_field(ArchivePageFields::FIELD_NAME_EVENT_HERO_CARD_LINK, $pageId);

        $title = is_string($title) ? trim(wp_strip_all_tags($title)) : '';
        $url = is_array($link) && is_string($link['url'] ?? null) ? trim($link['url']) : '';
        $linkTitle = is_array($link) && is_string($link['title'] ?? null)
            ? trim(wp_strip_all_tags($link['title']))
            : '';
        $target = is_array($link) && is_string($link['target'] ?? null) && $link['target'] !== ''
            ? $link['target']
            : '_self';

        if ($title === '' || $url === '' || $linkTitle === '') {
            return null;
        }

        return [
            'title' => $title,
            'link' => [
                'url' => esc_url($url),
                'title' => $linkTitle,
                'target' => $target,
                'rel' => $target === '_blank' ? 'noopener noreferrer' : '',
            ],
        ];
    }

    /** Resolve the configured badge taxonomy for the current archive. */
    private function getArchiveBadgeTaxonomy(?int $pageId, string $postType): string
    {
        if (!$pageId || $pageId <= 0) {
            return '';
        }

        $taxonomy = '';

        if (function_exists('get_field')) {
            $value = get_field(self::BADGE_TAXONOMY_FIELD_NAME, $pageId);
            $taxonomy = is_string($value) ? sanitize_key($value) : '';
        }

        if ($taxonomy === '') {
            $value = get_post_meta($pageId, self::BADGE_TAXONOMY_FIELD_NAME, true);
            $taxonomy = is_string($value) ? sanitize_key($value) : '';
        }

        if ($taxonomy === '' || !taxonomy_exists($taxonomy)) {
            return '';
        }

        $allowedTaxonomies = get_object_taxonomies($postType, 'names');

        return is_array($allowedTaxonomies) && in_array($taxonomy, $allowedTaxonomies, true)
            ? $taxonomy
            : '';
    }

    /** Resolve the badge label from the first matching term. */
    private function getBadgeLabel(PostObjectInterface $post, string $taxonomy): string
    {
        if ($taxonomy === '') {
            return '';
        }

        $terms = $post->getTerms([$taxonomy]);
        $term = is_array($terms) ? reset($terms) : false;

        return is_object($term) && !empty($term->name) && is_string($term->name)
            ? $term->name
            : '';
    }

    /** Return the date badge or taxonomy badge for archive cards. */
    private function getCardBadgeLabel(PostObjectInterface $post, string $postType, string $taxonomy): string
    {
        if ($this->shouldUseDateBadge($postType)) {
            return $this->getPublishedDateLabel($post);
        }

        return $this->getBadgeLabel($post, $taxonomy);
    }

    /** Use published dates as badges for news-style archive cards. */
    private function shouldUseDateBadge(string $postType): bool
    {
        return in_array($postType, self::DATE_BADGE_POST_TYPES, true);
    }

    /** Format the post's published date for the archive badge. */
    private function getPublishedDateLabel(PostObjectInterface $post): string
    {
        $timestamp = $post->getPublishedTime();

        if ($timestamp <= 0) {
            return '';
        }

        return wp_date(DateFormat::getDateFormat('date'), $timestamp);
    }

    /** Treat empty theme mods as unset values. */
    private function isUnsetThemeModValue(mixed $value): bool
    {
        return in_array($value, [null, false, '', 0, '0'], true);
    }
}
