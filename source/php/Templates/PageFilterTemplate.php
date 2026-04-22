<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

use LidingoCustomisation\AcfFields\PageFilterFields;
use Municipio\Helper\AcfService;
use Municipio\Helper\Template as MunicipioTemplate;
use Municipio\Helper\WpService;
use Municipio\Helper\Wpdb;
use Municipio\PostsList\Config\AppearanceConfig\DefaultAppearanceConfig;
use Municipio\PostsList\Config\FilterConfig\DefaultFilterConfig;
use Municipio\PostsList\Config\FilterConfig\TaxonomyFilterConfig\TaxonomyFilterConfig;
use Municipio\PostsList\Config\FilterConfig\TaxonomyFilterConfig\TaxonomyFilterType;
use Municipio\PostsList\Config\GetPostsConfig\DefaultGetPostsConfig;
use Municipio\PostsList\Config\GetPostsConfig\OrderDirection;
use Municipio\PostsList\ConfigMapper\PostsListConfigDTO;
use Municipio\PostsList\PostsListFactory;
use Municipio\SchemaData\Utils\SchemaToPostTypesResolver\SchemaToPostTypeResolver;
use WP_Taxonomy;

class PageFilterTemplate
{
    public const TEMPLATE_NAME = 'Filterlista';
    public const TEMPLATE_SLUG = 'page-filter.blade.php';
    private const QUERY_VAR_PREFIX = 'page_filter_';

    private string $viewPath;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
    }

    /** Register page filter template hooks. */
    public function addHooks(): void
    {
        add_action('init', [$this, 'registerTemplate'], 20);
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('Municipio/Admin/Gutenberg/TemplatesToInclude', [$this, 'extendGutenbergTemplates']);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 20);
    }

    /** Register the page filter template. */
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

    /** Prepare page filter view data. */
    public function customizeViewData(array $viewData): array
    {
        if (!$this->isPageFilterTemplate()) {
            return $viewData;
        }

        $pageId = get_queried_object_id();

        if (!is_int($pageId) || $pageId <= 0) {
            return $viewData;
        }

        $childIds = $this->getChildPageIds($pageId);
        $showSearch = $this->shouldShowSearch($pageId);
        $taxonomies = $this->getSelectedTaxonomies($pageId);
        $activeFilters = $this->hasActiveFilters($showSearch, $taxonomies);
        $postsListData = $this->getPostsListData($childIds, $showSearch, $taxonomies, $activeFilters, $pageId);
        $excerpt = get_post_field('post_excerpt', $pageId);

        $viewData = array_merge($viewData, $postsListData);
        $viewData['showSidebars'] = false;
        $viewData['hasSideMenu'] = false;
        $viewData['helperNavBeforeContent'] = true;
        $viewData['skipToMainContentLink'] = '#main-content';
        $viewData['pageFilterPreamble'] = is_string($excerpt) && $excerpt !== ''
            ? apply_filters('the_excerpt', $excerpt)
            : '';
        $viewData['pageFilterHasChildren'] = !empty($childIds);
        $viewData['pageFilterNoResultsText'] = $this->getNoResultsText($childIds, $activeFilters);
        $viewData['archiveLayoutPostType'] = 'page';
        $viewData['archiveLayoutPageId'] = $pageId;
        $viewData['archiveLayoutResetUrl'] = (string) get_permalink($pageId);
        $viewData['archiveLayoutFilterFormAction'] = (string) get_permalink($pageId) . '#page-filter-listing';
        $viewData['archiveLayoutHasActiveFilters'] = $activeFilters;
        $viewData['archiveLayoutYearOptions'] = [];
        $viewData['archiveLayoutSelectedYear'] = null;
        $viewData['archiveLayoutYearParameterName'] = '';
        $viewData['archiveLayoutCardMetaIcon'] = '';
        $viewData['archiveLayoutUsesDateBadge'] = false;
        $viewData['archiveLayoutBadgeTaxonomy'] = '';
        $viewData['getArchiveCardBadgeLabel'] = static fn(mixed $post = null): string => '';
        $viewData['getArchiveCardMeta'] = static fn(mixed $post = null): string => '';
        $viewData['getTaxonomyFilterSelectComponentArguments'] = $this->getTaxonomyFilterSelectArgumentsCallable($taxonomies, $childIds);

        return $viewData;
    }

    /** Build posts list data for direct child pages. */
    private function getPostsListData(array $childIds, bool $showSearch, array $taxonomies, bool $activeFilters, int $pageId): array
    {
        $taxonomyConfigs = array_map(
            static fn(WP_Taxonomy $taxonomy): TaxonomyFilterConfig => new TaxonomyFilterConfig($taxonomy, TaxonomyFilterType::SINGLESELECT),
            $taxonomies
        );
        $resetUrl = $activeFilters ? (string) get_permalink($pageId) : null;

        $getPostsConfig = new class($childIds) extends DefaultGetPostsConfig {
            public function __construct(private array $childIds)
            {
            }

            public function getPostTypes(): array
            {
                return ['page'];
            }

            public function getPostsPerPage(): int
            {
                return -1;
            }

            public function paginationEnabled(): bool
            {
                return false;
            }

            public function isFacettingTaxonomyQueryEnabled(): bool
            {
                return true;
            }

            public function getOrderBy(): string
            {
                return 'title';
            }

            public function getOrder(): OrderDirection
            {
                return OrderDirection::ASC;
            }

            public function getIncludedPostIds(): array
            {
                return !empty($this->childIds) ? $this->childIds : [0];
            }
        };

        $appearanceConfig = new class extends DefaultAppearanceConfig {
            public function shouldDisplayFeaturedImage(): bool
            {
                return true;
            }

            public function getNumberOfColumns(): int
            {
                return 3;
            }
        };

        $filterConfig = new class($showSearch, $taxonomyConfigs, $resetUrl) extends DefaultFilterConfig {
            public function __construct(
                private bool $showSearch,
                private array $taxonomyConfigs,
                private ?string $resetUrl
            ) {
            }

            public function isTextSearchEnabled(): bool
            {
                return $this->showSearch;
            }

            public function getTaxonomiesEnabledForFiltering(): array
            {
                return $this->taxonomyConfigs;
            }

            public function showReset(): bool
            {
                return $this->resetUrl !== null;
            }

            public function getResetUrl(): ?string
            {
                return $this->resetUrl;
            }

            public function getAnchor(): ?string
            {
                return 'page-filter-listing';
            }
        };

        $postsListConfigDto = new PostsListConfigDTO(
            $getPostsConfig,
            $appearanceConfig,
            $filterConfig,
            self::QUERY_VAR_PREFIX
        );

        $wpService = WpService::get();
        $acfService = AcfService::get();

        return (new PostsListFactory(
            $wpService,
            Wpdb::get(),
            new SchemaToPostTypeResolver($acfService, $wpService)
        ))
            ->create($postsListConfigDto)
            ->getData();
    }

    /** Return direct published child page IDs. */
    private function getChildPageIds(int $pageId): array
    {
        $childIds = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_parent' => $pageId,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'title',
            'order' => 'ASC',
            'suppress_filters' => false,
        ]);

        return is_array($childIds)
            ? array_values(array_filter(array_map('intval', $childIds), static fn(int $id): bool => $id > 0))
            : [];
    }

    /** Check whether text search is enabled for the current page. */
    private function shouldShowSearch(int $pageId): bool
    {
        $value = function_exists('get_field')
            ? get_field(PageFilterFields::FIELD_NAME_SHOW_SEARCH, $pageId)
            : get_post_meta($pageId, PageFilterFields::FIELD_NAME_SHOW_SEARCH, true);

        if ($value === null || $value === '') {
            return true;
        }

        return !in_array($value, [false, 0, '0'], true);
    }

    /** Resolve selected page taxonomies from ACF. */
    private function getSelectedTaxonomies(int $pageId): array
    {
        $value = function_exists('get_field')
            ? get_field(PageFilterFields::FIELD_NAME_TAXONOMIES, $pageId)
            : get_post_meta($pageId, PageFilterFields::FIELD_NAME_TAXONOMIES, true);

        $taxonomyNames = is_array($value) ? $value : [];
        $taxonomies = [];

        foreach ($taxonomyNames as $taxonomyName) {
            if (!is_string($taxonomyName)) {
                continue;
            }

            $taxonomyName = sanitize_key($taxonomyName);
            $taxonomy = get_taxonomy($taxonomyName);

            if (!$taxonomy instanceof WP_Taxonomy || !is_object_in_taxonomy('page', $taxonomyName)) {
                continue;
            }

            if (empty($taxonomy->public) && empty($taxonomy->show_ui)) {
                continue;
            }

            $taxonomies[] = $taxonomy;
        }

        return $taxonomies;
    }

    /** Build taxonomy select arguments limited to terms used by the child pages. */
    private function getTaxonomyFilterSelectArgumentsCallable(array $taxonomies, array $childIds): callable
    {
        return function () use ($taxonomies, $childIds): array {
            $selects = [];

            foreach ($taxonomies as $taxonomy) {
                if (!$taxonomy instanceof WP_Taxonomy) {
                    continue;
                }

                $options = $this->getTermOptionsForChildPages($taxonomy, $childIds);

                if (empty($options)) {
                    continue;
                }

                $select = [
                    'label' => apply_filters('Municipio/Archive/TaxonomyFilter/Label', $taxonomy->label, $taxonomy),
                    'name' => self::QUERY_VAR_PREFIX . $taxonomy->name,
                    'required' => false,
                    'placeholder' => apply_filters('Municipio/Archive/TaxonomyFilter/Placeholder', $taxonomy->label, $taxonomy),
                    'multiple' => false,
                    'options' => $options,
                ];
                $preselected = $this->getSelectedTermSlugsFromRequest($taxonomy->name);

                if (!empty($preselected)) {
                    $select['preselected'] = $preselected;
                }

                $selects[] = $select;
            }

            return $selects;
        };
    }

    /** Return term options for terms used by the direct child pages. */
    private function getTermOptionsForChildPages(WP_Taxonomy $taxonomy, array $childIds): array
    {
        if (empty($childIds)) {
            return [];
        }

        $terms = wp_get_object_terms($childIds, $taxonomy->name, [
            'fields' => 'all_with_object_id',
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (!is_array($terms) || is_wp_error($terms)) {
            return [];
        }

        $counts = [];
        $labels = [];

        foreach ($terms as $term) {
            if (!is_object($term) || empty($term->term_id) || empty($term->slug) || empty($term->name)) {
                continue;
            }

            $termId = (int) $term->term_id;
            $counts[$termId] = ($counts[$termId] ?? 0) + 1;
            $labels[$termId] = [
                'name' => (string) $term->name,
                'slug' => (string) $term->slug,
            ];
        }

        uasort($labels, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        $options = [];

        foreach ($labels as $termId => $label) {
            $options[$label['slug']] = sprintf('%s (%d)', $label['name'], $counts[$termId] ?? 0);
        }

        return $options;
    }

    /** Check whether any filter is active in the request. */
    private function hasActiveFilters(bool $showSearch, array $taxonomies): bool
    {
        if ($showSearch && $this->getRequestValue(self::QUERY_VAR_PREFIX . 'search') !== '') {
            return true;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy instanceof WP_Taxonomy && $this->getSelectedTermSlugsFromRequest($taxonomy->name) !== []) {
                return true;
            }
        }

        return false;
    }

    /** Return selected term slugs from GET for a taxonomy. */
    private function getSelectedTermSlugsFromRequest(string $taxonomyName): array
    {
        $value = $_GET[self::QUERY_VAR_PREFIX . $taxonomyName] ?? null;
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(static fn($slug): string => is_string($slug) ? sanitize_title(wp_unslash($slug)) : '', $values),
            static fn(string $slug): bool => $slug !== ''
        ));
    }

    /** Return a sanitized scalar request value. */
    private function getRequestValue(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? trim(sanitize_text_field(wp_unslash($value))) : '';
    }

    /** Return the empty-state message for the list. */
    private function getNoResultsText(array $childIds, bool $activeFilters): string
    {
        if (empty($childIds)) {
            return __('Det finns inga sidor att visa.', 'lidingo-customisation');
        }

        return $activeFilters
            ? __('Inga sidor matchar din filtrering.', 'lidingo-customisation')
            : __('Det finns inga sidor att visa.', 'lidingo-customisation');
    }

    private function isPageFilterTemplate(): bool
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return false;
        }

        return get_page_template_slug($objectId) === self::TEMPLATE_SLUG;
    }
}
