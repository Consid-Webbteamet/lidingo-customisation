<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

use Municipio\Helper\Template as MunicipioTemplate;

class ContentPageWithTocTemplate
{
    public const TEMPLATE_NAME = 'Undersida med innehållsförteckning';
    public const TEMPLATE_SLUG = 'content-page-with-toc.blade.php';
    private const DEFAULT_TEMPLATE_POST_TYPES = ['page', 'grundskola'];

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    /** Register content page with TOC template hooks. */
    public function addHooks(): void
    {
        add_action('init', [$this, 'registerTemplate'], 20);
        add_action('init', [$this, 'enablePageExcerptSupport'], 20);
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('Municipio/Admin/Gutenberg/TemplatesToInclude', [$this, 'extendGutenbergTemplates']);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData']);
        add_filter('option_modularity-sidebar-options', [$this, 'usePageSpecificSidebarOptions']);
    }

    /** Register the content page with TOC template. */
    public function registerTemplate(): void
    {
        if (!class_exists(MunicipioTemplate::class)) {
            return;
        }

        MunicipioTemplate::add(
            __(self::TEMPLATE_NAME, 'lidingo-customisation'),
            path_join($this->viewPath, self::TEMPLATE_SLUG),
            $this->getTemplatePostTypes()
        );
    }

    /** Enable excerpts for supported template post types. */
    public function enablePageExcerptSupport(): void
    {
        foreach ($this->getTemplatePostTypes() as $postType) {
            add_post_type_support($postType, 'excerpt');
        }
    }

    /** Add the template view path. */
    public function addViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            $viewPaths[] = $this->viewPath;
        }

        return $viewPaths;
    }

    /** Add the template to Gutenberg. */
    public function extendGutenbergTemplates(array $templates): array
    {
        if (!in_array(self::TEMPLATE_SLUG, $templates, true)) {
            $templates[] = self::TEMPLATE_SLUG;
        }

        return $templates;
    }

    /** Prepare content page with TOC view data. */
    public function customizeViewData(array $viewData): array
    {
        if (!$this->isTemplateActive()) {
            return $viewData;
        }

        $objectId = get_queried_object_id();
        $timestamp = is_int($objectId) ? get_post_timestamp($objectId, 'date') : false;
        $excerpt = is_int($objectId) ? get_post_field('post_excerpt', $objectId) : '';

        $viewData['hasSideMenu'] = false;
        $viewData['helperNavBeforeContent'] = true;
        $viewData['skipToMainContentLink'] = '#main-content';
        $viewData['contentPagePreamble'] = is_string($excerpt) && $excerpt !== ''
            ? apply_filters('the_excerpt', $excerpt)
            : '';
        $viewData['contentPagePublishedDate'] = is_int($timestamp)
            ? wp_date((string) get_option('date_format', 'j F Y'), $timestamp)
            : '';

        return $viewData;
    }

    /** Use page-specific Modularity sidebar options on the TOC template. */
    public function usePageSpecificSidebarOptions($options)
    {
        if (is_admin()) {
            return $options;
        }

        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return $options;
        }

        if (get_page_template_slug($objectId) !== self::TEMPLATE_SLUG) {
            return $options;
        }

        $pageOptions = get_post_meta($objectId, 'modularity-sidebar-options', true);

        return is_array($pageOptions) ? $pageOptions : $options;
    }

    private function isTemplateActive(): bool
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return false;
        }

        return get_page_template_slug($objectId) === self::TEMPLATE_SLUG;
    }

    private function getTemplatePostTypes(): array
    {
        $postTypes = apply_filters(
            'lidingo_customisation/page_template_post_types',
            self::DEFAULT_TEMPLATE_POST_TYPES
        );

        if (!is_array($postTypes)) {
            return self::DEFAULT_TEMPLATE_POST_TYPES;
        }

        $postTypes = array_values(array_filter(
            array_map(static fn($postType): string => is_string($postType) ? sanitize_key($postType) : '', $postTypes),
            static fn(string $postType): bool => $postType !== ''
        ));

        return !empty($postTypes) ? $postTypes : self::DEFAULT_TEMPLATE_POST_TYPES;
    }
}
