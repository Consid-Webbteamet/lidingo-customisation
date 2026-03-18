<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

use Municipio\Helper\Template as MunicipioTemplate;

class ArticlePageTemplate
{
    public const TEMPLATE_NAME = 'Artikelsida';
    public const TEMPLATE_SLUG = 'article-page.blade.php';
    private const SINGLE_TEMPLATE_SLUG = 'single-article.blade.php';
    private const DEFAULT_ARTICLE_POST_TYPES = ['post', 'news', 'nyheter'];

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    public function addHooks(): void
    {
        add_action('init', [$this, 'registerTemplate'], 20);
        add_filter('template_include', [$this, 'useSingleArticleTemplate'], 9);
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('Municipio/Admin/Gutenberg/TemplatesToInclude', [$this, 'extendGutenbergTemplates']);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData']);
    }

    public function registerTemplate(): void
    {
        if (!class_exists(MunicipioTemplate::class)) {
            return;
        }

        MunicipioTemplate::add(
            __(self::TEMPLATE_NAME, 'lidingo-customisation'),
            path_join($this->viewPath, self::TEMPLATE_SLUG),
            ['page']
        );
    }

    public function addViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            $viewPaths[] = $this->viewPath;
        }

        return $viewPaths;
    }

    public function extendGutenbergTemplates(array $templates): array
    {
        if (!in_array(self::TEMPLATE_SLUG, $templates, true)) {
            $templates[] = self::TEMPLATE_SLUG;
        }

        return $templates;
    }

    public function useSingleArticleTemplate(string $template): string
    {
        if (!$this->isArticleSingular()) {
            return $template;
        }

        $singleArticleTemplate = path_join($this->viewPath, self::SINGLE_TEMPLATE_SLUG);

        return file_exists($singleArticleTemplate) ? $singleArticleTemplate : $template;
    }

    public function customizeViewData(array $viewData): array
    {
        if (!$this->shouldApplyArticleLayout()) {
            return $viewData;
        }

        $objectId = get_queried_object_id();
        $timestamp = is_int($objectId) ? get_post_timestamp($objectId, 'date') : false;
        $excerpt = is_int($objectId) ? get_post_field('post_excerpt', $objectId) : '';

        $viewData['hasSideMenu'] = false;
        $viewData['showSidebars'] = false;
        $viewData['helperNavBeforeContent'] = true;
        $viewData['skipToMainContentLink'] = '#main-content';
        $viewData['articlePagePreamble'] = is_string($excerpt) && $excerpt !== ''
            ? apply_filters('the_excerpt', $excerpt)
            : '';
        $viewData['articlePagePublishedDate'] = is_int($timestamp)
            ? wp_date((string) get_option('date_format', 'j F Y'), $timestamp)
            : '';

        return $viewData;
    }

    private function shouldApplyArticleLayout(): bool
    {
        return $this->isArticlePageTemplate() || $this->isArticleSingular();
    }

    private function isArticlePageTemplate(): bool
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return false;
        }

        return is_page() && get_page_template_slug($objectId) === self::TEMPLATE_SLUG;
    }

    private function isArticleSingular(): bool
    {
        return is_singular($this->getArticlePostTypes());
    }

    private function getArticlePostTypes(): array
    {
        // Opt in additional singular post types here without adding new single templates.
        // Example: add_filter('lidingo_customisation/article_post_types', fn (array $types) => [...$types, 'pressrelease']);
        $postTypes = apply_filters(
            'lidingo_customisation/article_post_types',
            self::DEFAULT_ARTICLE_POST_TYPES
        );

        if (!is_array($postTypes)) {
            return self::DEFAULT_ARTICLE_POST_TYPES;
        }

        $postTypes = array_values(array_filter(
            array_map(
                static fn($postType) => is_string($postType) ? sanitize_key($postType) : '',
                $postTypes
            ),
            static fn(string $postType) => $postType !== ''
        ));

        return !empty($postTypes) ? $postTypes : self::DEFAULT_ARTICLE_POST_TYPES;
    }
}
