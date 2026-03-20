<?php

declare(strict_types=1);

namespace LidingoCustomisation\Search;

use WP_Post_Type;
use WP_Query;

class SearchPage
{
    private const TEMPLATE_SLUG = 'search.blade.php';
    private const TYPE_SLUG_ALL = 'alla';
    private const TYPE_SLUG_PAGES = 'sidor';
    private const TYPE_SLUG_NEWS = 'nyheter';
    private const TYPE_SLUG_EVENTS = 'evenemang';
    private const EXCLUDED_POST_TYPES = ['attachment'];

    private string $viewPath;
    private array $countCache = [];
    private SearchTextFormatter $textFormatter;
    private SearchResultBuilder $resultBuilder;

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = untrailingslashit(
            $viewPath ?? LIDINGO_CUSTOMISATION_PATH . 'source/views'
        );
        $this->textFormatter = new SearchTextFormatter();
        $this->resultBuilder = new SearchResultBuilder($this->textFormatter);
    }

    /** Register the search page hooks. */
    public function addHooks(): void
    {
        add_filter('Municipio/viewPaths', [$this, 'addViewPath']);
        add_filter('template_include', [$this, 'useSearchTemplate'], 9);
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 15);
        add_filter('query_vars', [$this, 'registerQueryVars']);
        add_action('pre_get_posts', [$this, 'filterMainSearchQuery']);
    }

    /** Prepend the local view path so the custom search template can be resolved. */
    public function addViewPath(array $viewPaths): array
    {
        if (!in_array($this->viewPath, $viewPaths, true)) {
            array_unshift($viewPaths, $this->viewPath);
        }

        return $viewPaths;
    }

    /** Register search-related query vars so Municipio pagination preserves them. */
    public function registerQueryVars(array $queryVars): array
    {
        foreach (['s', 'type', 'paged'] as $queryVar) {
            if (!in_array($queryVar, $queryVars, true)) {
                $queryVars[] = $queryVar;
            }
        }

        return $queryVars;
    }

    /** Swap in the custom search template when rendering the search page. */
    public function useSearchTemplate(string $template): string
    {
        if (!$this->shouldUseSearchLayout()) {
            return $template;
        }

        $searchTemplate = path_join($this->viewPath, self::TEMPLATE_SLUG);

        return file_exists($searchTemplate) ? $searchTemplate : $template;
    }

    /** Build the extra view data consumed by the custom search template. */
    public function customizeViewData(array $viewData): array
    {
        if (!$this->shouldUseSearchLayout()) {
            return $viewData;
        }

        $keyword = $this->getSearchKeyword();
        $typeGroups = $this->getTypeGroups();
        $activeType = $this->getActiveTypeSlug($typeGroups);
        $searchTerms = $this->textFormatter->getSearchTerms($keyword);

        $viewData['showSidebars'] = false;
        $viewData['hasSideMenu'] = false;
        $viewData['helperNavBeforeContent'] = false;
        $viewData['skipToMainContentLink'] = '#main-content';

        $viewData['searchPageKeyword'] = $keyword;
        $viewData['searchPageActiveType'] = $activeType;
        $viewData['searchPageAllCount'] = $keyword !== ''
            ? $this->getSearchCount($keyword, $this->getAllGroupedPostTypes($typeGroups))
            : 0;
        $viewData['searchPageTypeButtons'] = $this->buildTypeButtons(
            $keyword,
            $typeGroups,
            $viewData['searchPageAllCount'],
            $activeType
        );
        $viewData['searchPageResults'] = $this->resultBuilder->buildResults(
            $viewData['posts'] ?? [],
            $searchTerms,
            $typeGroups
        );
        $viewData['searchPageHighlightedKeyword'] = $keyword !== ''
            ? sprintf('"%s"', esc_html($keyword))
            : '';
        $viewData['showPagination'] = false;
        $viewData['paginationList'] = [];
        $viewData['currentPagePagination'] = 1;
        $viewData['searchPagePaginationPreviousUrl'] = '';
        $viewData['searchPagePaginationNextUrl'] = '';
        $viewData['searchPagePaginationHasPrevious'] = false;
        $viewData['searchPagePaginationHasNext'] = false;

        $wpQuery = $GLOBALS['wp_query'] ?? null;
        $postsPerPage = max(1, (int) get_option('posts_per_page'));
        $foundPosts = $wpQuery instanceof WP_Query ? (int) $wpQuery->found_posts : 0;
        $totalPages = (int) ceil($foundPosts / $postsPerPage);

        if ($totalPages > 1) {
            $currentPage = max(1, (int) get_query_var('paged'));

            $viewData['showPagination'] = true;
            $viewData['currentPagePagination'] = $currentPage;
            $viewData['paginationList'] = $this->buildPaginationList($totalPages, $keyword, $activeType);
            $viewData['searchPagePaginationHasPrevious'] = $currentPage > 1;
            $viewData['searchPagePaginationHasNext'] = $currentPage < $totalPages;
            $viewData['searchPagePaginationPreviousUrl'] = $currentPage > 1
                ? $this->buildPaginationUrl($currentPage - 1, $keyword, $activeType)
                : '';
            $viewData['searchPagePaginationNextUrl'] = $currentPage < $totalPages
                ? $this->buildPaginationUrl($currentPage + 1, $keyword, $activeType)
                : '';
        }

        return $viewData;
    }

    /** Restrict the main search query when a type filter is active. */
    public function filterMainSearchQuery(WP_Query $query): void
    {
        if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
            return;
        }

        $typeGroups = $this->getTypeGroups();
        $activeType = $this->getActiveTypeSlug($typeGroups);
        $postTypes = $activeType === null
            ? $this->getAllGroupedPostTypes($typeGroups)
            : ($typeGroups[$activeType]['post_types'] ?? []);

        if (empty($postTypes)) {
            return;
        }

        $query->set('post_type', $postTypes);
    }

    /** Only apply the custom behavior on the public search page. */
    private function shouldUseSearchLayout(): bool
    {
        return !is_admin() && is_search();
    }

    /** Read and sanitize the search keyword from the request. */
    private function getSearchKeyword(): string
    {
        return isset($_GET['s'])
            ? sanitize_text_field(wp_unslash((string) $_GET['s']))
            : '';
    }

    /** Resolve the active type slug from the request when it matches a known group. */
    private function getActiveTypeSlug(array $typeGroups): ?string
    {
        if (!isset($_GET['type'])) {
            return null;
        }

        $type = sanitize_title(wp_unslash((string) $_GET['type']));

        if ($type === '' || $type === self::TYPE_SLUG_ALL) {
            return null;
        }

        return array_key_exists($type, $typeGroups) ? $type : null;
    }

    /** Build the visible type groups from preferred and dynamic post types. */
    private function getTypeGroups(): array
    {
        $postTypes = $this->getSearchablePostTypes();
        $groups = [];

        $preferredGroups = [
            self::TYPE_SLUG_PAGES => [
                'label' => __('Sidor', 'lidingo-customisation'),
                'post_types' => ['page'],
                'priority' => 10,
            ],
            self::TYPE_SLUG_NEWS => [
                'label' => __('Nyheter', 'lidingo-customisation'),
                'post_types' => ['post', 'news', 'nyheter'],
                'priority' => 20,
            ],
            self::TYPE_SLUG_EVENTS => [
                'label' => __('Evenemang', 'lidingo-customisation'),
                'post_types' => ['event'],
                'priority' => 30,
            ],
        ];

        foreach ($preferredGroups as $slug => $group) {
            $availablePostTypes = array_values(array_filter(
                $group['post_types'],
                static fn(string $postType): bool => isset($postTypes[$postType])
            ));

            if (empty($availablePostTypes)) {
                continue;
            }

            $groups[$slug] = [
                'slug' => $slug,
                'label' => $group['label'],
                'post_types' => $availablePostTypes,
                'priority' => $group['priority'],
            ];

            foreach ($availablePostTypes as $postType) {
                unset($postTypes[$postType]);
            }
        }

        $usedSlugs = array_keys($groups);

        foreach ($postTypes as $postType => $postTypeObject) {
            $label = $this->getPostTypeLabel($postTypeObject);
            $slug = $this->buildUniqueTypeSlug($label, $postType, $usedSlugs);
            $usedSlugs[] = $slug;

            $groups[$slug] = [
                'slug' => $slug,
                'label' => $label,
                'post_types' => [$postType],
                'priority' => 100,
            ];
        }

        uasort(
            $groups,
            static function (array $left, array $right): int {
                $priority = $left['priority'] <=> $right['priority'];

                if ($priority !== 0) {
                    return $priority;
                }

                return strcasecmp($left['label'], $right['label']);
            }
        );

        return $groups;
    }

    /** Return public post types that should be available in the search UI. */
    private function getSearchablePostTypes(): array
    {
        $postTypes = get_post_types(['public' => true], 'objects');

        if (!isset($postTypes['page'])) {
            $pageObject = get_post_type_object('page');

            if ($pageObject instanceof WP_Post_Type) {
                $postTypes['page'] = $pageObject;
            }
        }

        return array_filter(
            $postTypes,
            static function ($postTypeObject): bool {
                if (!$postTypeObject instanceof WP_Post_Type) {
                    return false;
                }

                if (in_array($postTypeObject->name, self::EXCLUDED_POST_TYPES, true)) {
                    return false;
                }

                return !$postTypeObject->exclude_from_search;
            }
        );
    }

    /** Create the filter button data, including counts and active state. */
    private function buildTypeButtons(string $keyword, array $typeGroups, int $allCount, ?string $activeType): array
    {
        $buttons = [[
            'slug' => self::TYPE_SLUG_ALL,
            'label' => __('Alla träffar', 'lidingo-customisation'),
            'count' => $allCount,
            'url' => $this->buildSearchUrl($keyword, null),
            'isActive' => $activeType === null,
        ]];

        foreach ($typeGroups as $slug => $group) {
            $count = $keyword !== ''
                ? $this->getSearchCount($keyword, $group['post_types'])
                : 0;

            if ($count < 1 && $activeType !== $slug) {
                continue;
            }

            $buttons[] = [
                'slug' => $slug,
                'label' => $group['label'],
                'count' => $count,
                'url' => $this->buildSearchUrl($keyword, $slug),
                'isActive' => $activeType === $slug,
            ];
        }

        return $buttons;
    }

    /** Count matching posts for a keyword and a set of post types. */
    private function getSearchCount(string $keyword, array $postTypes): int
    {
        sort($postTypes);
        $cacheKey = md5($keyword . '|' . implode(',', $postTypes));

        if (isset($this->countCache[$cacheKey])) {
            return $this->countCache[$cacheKey];
        }

        $query = new WP_Query([
            's' => $keyword,
            'post_type' => $postTypes,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'ignore_sticky_posts' => true,
            'fields' => 'ids',
        ]);

        $this->countCache[$cacheKey] = (int) $query->found_posts;

        return $this->countCache[$cacheKey];
    }

    /** Flatten all grouped post types into a unique list. */
    private function getAllGroupedPostTypes(array $typeGroups): array
    {
        $postTypes = [];

        foreach ($typeGroups as $group) {
            foreach ($group['post_types'] as $postType) {
                $postTypes[$postType] = $postType;
            }
        }

        return array_values($postTypes);
    }

    /** Build a search URL while preserving the current keyword and optional type. */
    private function buildSearchUrl(string $keyword, ?string $typeSlug): string
    {
        $args = ['s' => $keyword];

        if ($typeSlug !== null) {
            $args['type'] = $typeSlug;
        }

        return (string) add_query_arg($args, home_url('/'));
    }

    /** Build pagination links for the current search query. */
    private function buildPaginationList(int $totalPages, string $keyword, ?string $activeType): array
    {
        $paginationList = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $paginationList[] = [
                'href' => $this->buildPaginationUrl($page, $keyword, $activeType),
                'label' => (string) $page,
            ];
        }

        return $paginationList;
    }

    /** Build a pagination URL for a specific search results page. */
    private function buildPaginationUrl(int $page, string $keyword, ?string $activeType): string
    {
        return (string) add_query_arg('paged', $page, $this->getCurrentSearchBaseUrl($keyword, $activeType));
    }

    /** Build the current search URL without pagination so links preserve active filters. */
    private function getCurrentSearchBaseUrl(string $keyword, ?string $activeType): string
    {
        $args = [];

        if ($keyword !== '') {
            $args['s'] = $keyword;
        }

        if ($activeType !== null) {
            $args['type'] = $activeType;
        }

        return (string) add_query_arg($args, home_url('/'));
    }

    /** Return the display label for a post type, including local aliases. */
    private function getPostTypeLabel(WP_Post_Type $postTypeObject): string
    {
        return match ($postTypeObject->name) {
            'page' => __('Sidor', 'lidingo-customisation'),
            'post', 'news', 'nyheter' => __('Nyheter', 'lidingo-customisation'),
            'event' => __('Evenemang', 'lidingo-customisation'),
            default => $postTypeObject->labels->name ?? $postTypeObject->label ?? $postTypeObject->name,
        };
    }

    /** Ensure dynamically generated type slugs stay unique across all groups. */
    private function buildUniqueTypeSlug(string $label, string $postType, array $usedSlugs): string
    {
        $slug = sanitize_title($label);

        if ($slug === '') {
            $slug = sanitize_title($postType);
        }

        if (!in_array($slug, $usedSlugs, true)) {
            return $slug;
        }

        return sanitize_title($postType);
    }
}
