<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

use Municipio\Helper\Template as MunicipioTemplate;

class ContentPageTemplate
{
    public const TEMPLATE_NAME = 'Undersida';
    public const TEMPLATE_SLUG = 'content-page.blade.php';

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    /** Register content page template hooks. */
    public function addHooks(): void
    {
        add_action('init', [$this, 'registerTemplate'], 20);
        add_action('init', [$this, 'enablePageExcerptSupport'], 20);
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('Municipio/Admin/Gutenberg/TemplatesToInclude', [$this, 'extendGutenbergTemplates']);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData']);
    }

    /** Register the content page template. */
    public function registerTemplate(): void
    {
        if (!class_exists(MunicipioTemplate::class)) {
            return;
        }

        MunicipioTemplate::add(
            __(self::TEMPLATE_NAME, 'lidingo-customisation'),
            path_join($this->viewPath, self::TEMPLATE_SLUG),
            PageTemplatePostTypes::get()
        );
    }

    /** Enable excerpts for supported template post types. */
    public function enablePageExcerptSupport(): void
    {
        foreach (PageTemplatePostTypes::get() as $postType) {
            add_post_type_support($postType, 'excerpt');
        }
    }

    /** Add the content template view path. */
    public function addViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            $viewPaths[] = $this->viewPath;
        }

        return $viewPaths;
    }

    /** Add the content template to Gutenberg. */
    public function extendGutenbergTemplates(array $templates): array
    {
        if (!in_array(self::TEMPLATE_SLUG, $templates, true)) {
            $templates[] = self::TEMPLATE_SLUG;
        }

        return $templates;
    }

    /** Prepare content page view data. */
    public function customizeViewData(array $viewData): array
    {
        if (!$this->isContentPageTemplate()) {
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

    private function isContentPageTemplate(): bool
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return false;
        }

        return get_page_template_slug($objectId) === self::TEMPLATE_SLUG;
    }

}
