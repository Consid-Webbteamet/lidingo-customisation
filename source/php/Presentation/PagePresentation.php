<?php

declare(strict_types=1);

namespace LidingoCustomisation\Presentation;

use LidingoCustomisation\Templates\LandingPageTemplate;

class PagePresentation
{
    /** Register page-level presentation filters and view overrides. */
    public function addHooks(): void
    {
        add_filter('body_class', [$this, 'addFrontendBodyClasses'], 20, 1);
        add_filter('theme_page_templates', [$this, 'customizeEditorPageTemplates'], 20, 4);
        add_filter('Municipio/Template/viewData', [$this, 'adjustContentNoticePlacement'], 20, 1);
        add_filter('Municipio/Template/viewData', [$this, 'adjustPostTypeArchiveBreadcrumb'], 30, 1);
        add_filter('ComponentLibrary/ViewPaths', [$this, 'prependComponentLibraryViewPath'], 20, 1);
        add_filter('/Modularity/externalViewPath', [$this, 'addModularityExternalViewPaths']);
    }

    /** Add body classes used by frontend page-level styling. */
    public function addFrontendBodyClasses(array $classes): array
    {
        if (!$this->shouldUseFullPageBackground()) {
            return $classes;
        }

        $classes[] = 'lidingo-full-page-background';

        return array_values(array_unique($classes));
    }

    /** Print inline CSS variables for the full-page background image. */
    public function printFullPageBackgroundStyles(): void
    {
        if (!$this->shouldUseFullPageBackground()) {
            return;
        }

        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return;
        }

        $backgroundImage = get_the_post_thumbnail_url($objectId, 'full');

        if (!is_string($backgroundImage) || $backgroundImage === '') {
            return;
        }

        printf(
            '<style id="lidingo-customisation-full-page-background">body.lidingo-full-page-background{--lidingo-full-page-background-image:url("%s");}</style>',
            esc_url_raw($backgroundImage)
        );
    }

    /** Move content notices above the hero on the one-page template. */
    public function adjustContentNoticePlacement(array $viewData): array
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return $viewData;
        }

        if (get_page_template_slug($objectId) !== 'one-page.blade.php') {
            return $viewData;
        }

        $viewData['renderContentNoticesBeforeHero'] = true;

        return $viewData;
    }

    /** Build breadcrumbs from the assigned archive page for mapped post type singles. */
    public function adjustPostTypeArchiveBreadcrumb(array $viewData): array
    {
        if (!is_singular()) {
            return $viewData;
        }

        $postType = get_post_type();

        if (!is_string($postType) || in_array($postType, ['page', 'attachment'], true)) {
            return $viewData;
        }

        $archivePageId = (int) get_option('page_for_' . $postType);

        if ($archivePageId <= 0) {
            return $viewData;
        }

        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return $viewData;
        }

        $breadcrumbMenu = is_array($viewData['breadcrumbMenu'] ?? null)
            ? $viewData['breadcrumbMenu']
            : [];
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
        $ancestorIds = array_reverse(array_filter(array_map('intval', get_post_ancestors($archivePageId))));

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
            'label' => get_the_title($archivePageId) ?: __('Untitled page', 'municipio'),
            'href' => get_permalink($archivePageId),
            'current' => false,
            'icon' => 'chevron_right',
        ];

        $items[] = [
            'label' => get_the_title($objectId) ?: __('Untitled page', 'municipio'),
            'current' => true,
            'icon' => 'chevron_right',
        ];

        $breadcrumbMenu['items'] = $items;
        $viewData['breadcrumbMenu'] = $breadcrumbMenu;

        return $viewData;
    }

    /** Hide editor templates that should not be available for pages. */
    public function customizeEditorPageTemplates(array $pageTemplates, \WP_Theme $theme, ?\WP_Post $post, string $postType): array
    {
        if ($postType !== 'page') {
            return $pageTemplates;
        }

        unset($pageTemplates['page-centered.blade.php']);

        if (isset($pageTemplates['one-page.blade.php'])) {
            $pageTemplates['one-page.blade.php'] = __('Startsida', 'lidingo-customisation');
        }

        return $pageTemplates;
    }

    /** Point Modularity to the package-local view overrides. */
    public function addModularityExternalViewPaths(array $paths): array
    {
        // Blade prepends paths in the order they are passed, so keep the
        // local override first and the Municipio module path second.
        $paths['mod-posts'] = [
            LIDINGO_CUSTOMISATION_PATH . 'source/modularity/mod-posts/views',
            get_template_directory() . '/Modularity/source/php/Module/Posts/views',
        ];

        return $paths;
    }

    /** Prioritize package-local component view overrides. */
    public function prependComponentLibraryViewPath(array $paths): array
    {
        $viewPath = LIDINGO_CUSTOMISATION_PATH . 'source/component-library/views';

        return array_values(array_unique(array_merge([$viewPath], $paths)));
    }

    /** Determine whether the current page should use a full-page background image. */
    private function shouldUseFullPageBackground(): bool
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return false;
        }

        $templateSlug = get_page_template_slug($objectId);
        $allowedTemplates = [
            'one-page.blade.php',
            LandingPageTemplate::TEMPLATE_SLUG,
        ];

        if (!is_front_page() && !in_array($templateSlug, $allowedTemplates, true)) {
            return false;
        }

        if (!$this->hasStepQueryParam()) {
            return false;
        }

        return has_post_thumbnail($objectId);
    }

    /** Detect Event plugin step URLs like ?45-step=1 in a domain-agnostic way. */
    private function hasStepQueryParam(): bool
    {
        foreach (array_keys($_GET) as $queryKey) {
            if (!is_string($queryKey)) {
                continue;
            }

            if (preg_match('/^\d+-step$/', $queryKey) === 1) {
                return true;
            }
        }

        return false;
    }
}
