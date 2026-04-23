<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

use Municipio\Helper\Template as MunicipioTemplate;

class LandingPageTemplate
{
    public const TEMPLATE_NAME = 'Landningssida';
    public const TEMPLATE_SLUG = 'landing-page.blade.php';

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    /** Register landing page template hooks. */
    public function addHooks(): void
    {
        add_action('init', [$this, 'registerTemplate'], 20);
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('Municipio/Admin/Gutenberg/TemplatesToInclude', [$this, 'extendGutenbergTemplates']);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData']);
    }

    /** Register the landing page template. */
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

    /** Add the landing template view path. */
    public function addViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            $viewPaths[] = $this->viewPath;
        }

        return $viewPaths;
    }

    /** Add the landing template to Gutenberg. */
    public function extendGutenbergTemplates(array $templates): array
    {
        if (!in_array(self::TEMPLATE_SLUG, $templates, true)) {
            $templates[] = self::TEMPLATE_SLUG;
        }

        return $templates;
    }

    /** Prepare landing page view data. */
    public function customizeViewData(array $viewData): array
    {
        if (!$this->isLandingPageTemplate()) {
            return $viewData;
        }

        $viewData['hasSideMenu'] = false;
        $viewData['helperNavBeforeContent'] = true;
        $viewData['skipToMainContentLink'] = '#main-content';
        $viewData['renderContentNoticesBeforeHero'] = true;

        return $viewData;
    }

    private function isLandingPageTemplate(): bool
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return false;
        }

        return get_page_template_slug($objectId) === self::TEMPLATE_SLUG;
    }

}
