<?php

declare(strict_types=1);

namespace LidingoCustomisation\Archives;

use Municipio\Helper\DateFormat;
use Municipio\PostObject\PostObjectInterface;
use WP_Post;
use WP_Post_Type;

class ArchiveLayout
{
    public const TEMPLATE_SLUG = 'archive-post-type.blade.php';
    public const BADGE_TAXONOMY_FIELD_NAME = 'lidingo_archive_badge_taxonomy';
    private const DATE_BADGE_POST_TYPES = ['news', 'nyheter'];

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    public function addHooks(): void
    {
        add_action('init', [$this, 'registerArchiveOrderDirectionFilters'], 20);
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('template_include', [$this, 'useArchiveTemplate'], 9);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 15);
    }

    public function registerArchiveOrderDirectionFilters(): void
    {
        foreach ($this->getSupportedPostTypes() as $postType) {
            add_filter(
                'theme_mod_archive_' . $postType . '_order_direction',
                fn($value) => $this->normalizeArchiveOrderDirection($value, $postType)
            );
        }
    }

    public function addViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            $viewPaths[] = $this->viewPath;
        }

        return $viewPaths;
    }

    public function useArchiveTemplate(string $template): string
    {
        if (!$this->shouldUseArchiveLayout()) {
            return $template;
        }

        $archiveTemplate = path_join($this->viewPath, self::TEMPLATE_SLUG);

        return file_exists($archiveTemplate) ? $archiveTemplate : $template;
    }

    public function customizeViewData(array $viewData): array
    {
        if (!$this->shouldUseArchiveLayout()) {
            return $viewData;
        }

        $postType = $this->getCurrentPostType();

        if ($postType === null) {
            return $viewData;
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

    private function shouldUseArchiveLayout(): bool
    {
        $postType = $this->getCurrentPostType();

        return is_post_type_archive() && $postType !== null && in_array($postType, $this->getSupportedPostTypes(), true);
    }

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

    private function getCurrentPostType(): ?string
    {
        $queriedObject = get_queried_object();

        if ($queriedObject instanceof WP_Post_Type && !empty($queriedObject->name)) {
            return sanitize_key((string) $queriedObject->name);
        }

        global $wp_query;

        $postType = $wp_query->query['post_type'] ?? null;

        if (is_string($postType) && $postType !== '') {
            return sanitize_key($postType);
        }

        return null;
    }

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

    private function shouldDisplayArchiveHeroImage(?WP_Post $page, array $viewData): bool
    {
        if ($page instanceof WP_Post) {
            if (function_exists('get_field')) {
                return (bool) get_field('post_single_show_featured_image', $page->ID);
            }

            return (bool) get_post_meta($page->ID, 'post_single_show_featured_image', true);
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

    private function hasActiveFilters(mixed $filterConfig): bool
    {
        return is_object($filterConfig) && method_exists($filterConfig, 'getResetUrl') && !empty($filterConfig->getResetUrl());
    }

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

    private function getCardBadgeLabel(PostObjectInterface $post, string $postType, string $taxonomy): string
    {
        if ($this->shouldUseDateBadge($postType)) {
            return $this->getPublishedDateLabel($post);
        }

        return $this->getBadgeLabel($post, $taxonomy);
    }

    private function shouldUseDateBadge(string $postType): bool
    {
        return in_array($postType, self::DATE_BADGE_POST_TYPES, true);
    }

    private function getPublishedDateLabel(PostObjectInterface $post): string
    {
        $timestamp = $post->getPublishedTime();

        if ($timestamp <= 0) {
            return '';
        }

        return wp_date(DateFormat::getDateFormat('date'), $timestamp);
    }

    private function isUnsetThemeModValue(mixed $value): bool
    {
        return in_array($value, [null, false, '', 0, '0'], true);
    }
}
