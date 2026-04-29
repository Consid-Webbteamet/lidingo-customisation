<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\Posts;

use WP_Post;

class FeaturedNewsPosts
{
    private const LEFT_CLASS = 'aktuellt-featured-left';
    private const RIGHT_CLASS = 'aktuellt-featured-right';

    /** @var array<int, int[]> */
    private array $manualPostIdsByPostId = [];

    public function addHooks(): void
    {
        add_filter('Modularity/Block/Data', [$this, 'excludeFeaturedPostsFromList'], 60, 3);
    }

    public function excludeFeaturedPostsFromList(array $viewData, array $block, object $module): array
    {
        if (!$this->isRightFeaturedPostsBlock($block)) {
            return $viewData;
        }

        $excludedPostIds = $this->getLeftFeaturedPostIds();

        if ($excludedPostIds === []) {
            return $viewData;
        }

        $currentPage = $this->getCurrentPageNumber($viewData, $module);
        $query = new \WP_Query($this->buildReplacementQueryArgs($viewData, $excludedPostIds, $currentPage));
        $viewData['posts'] = $this->preparePostsForView($query->get_posts(), $module);
        $viewData['stickyPosts'] = [];
        $viewData['maxNumPages'] = $query->max_num_pages;
        $viewData['paginationArguments'] = $this->getPaginationArguments($viewData, $module, $currentPage);

        return $viewData;
    }

    private function isRightFeaturedPostsBlock(array $block): bool
    {
        if (($block['name'] ?? '') !== 'acf/posts') {
            return false;
        }

        return $this->classListContains($block['className'] ?? $block['attrs']['className'] ?? '', self::RIGHT_CLASS);
    }

    private function getLeftFeaturedPostIds(): array
    {
        $post = get_post();

        if (!$post instanceof WP_Post) {
            return [];
        }

        if (!array_key_exists($post->ID, $this->manualPostIdsByPostId)) {
            $this->manualPostIdsByPostId[$post->ID] = $this->findLeftFeaturedPostIds(
                parse_blocks((string) $post->post_content)
            );
        }

        return $this->manualPostIdsByPostId[$post->ID];
    }

    private function findLeftFeaturedPostIds(array $blocks): array
    {
        foreach ($blocks as $block) {
            if ($this->isLeftFeaturedManualPostsBlock($block)) {
                return array_values(array_filter(
                    array_map('intval', (array) ($block['attrs']['data']['posts_data_posts'] ?? []))
                ));
            }

            $innerBlocks = $block['innerBlocks'] ?? [];
            if (is_array($innerBlocks) && $innerBlocks !== []) {
                $postIds = $this->findLeftFeaturedPostIds($innerBlocks);

                if ($postIds !== []) {
                    return $postIds;
                }
            }
        }

        return [];
    }

    private function isLeftFeaturedManualPostsBlock(array $block): bool
    {
        if (($block['blockName'] ?? '') !== 'acf/posts') {
            return false;
        }

        $attrs = $block['attrs'] ?? [];
        $data = is_array($attrs['data'] ?? null) ? $attrs['data'] : [];

        return $this->classListContains((string) ($attrs['className'] ?? ''), self::LEFT_CLASS)
            && ($data['posts_data_source'] ?? '') === 'manual';
    }

    private function buildReplacementQueryArgs(array $viewData, array $excludedPostIds, int $currentPage): array
    {
        $orderBy = (string) ($viewData['posts_sort_by'] ?? 'date');
        $order = (string) ($viewData['posts_sort_order'] ?? 'desc');

        return [
            'post_type' => (string) ($viewData['posts_data_post_type'] ?? 'post'),
            'post_status' => is_user_logged_in() ? ['publish', 'inherit', 'private'] : ['publish', 'inherit'],
            'posts_per_page' => $this->getPostsPerPage($viewData),
            'orderby' => $orderBy !== 'false' ? $orderBy : 'date',
            'order' => strtoupper($order) === 'ASC' ? 'ASC' : 'DESC',
            'post__not_in' => $excludedPostIds,
            'ignore_sticky_posts' => true,
            'post_password' => false,
            'suppress_filters' => false,
            'paged' => $currentPage,
        ];
    }

    private function preparePostsForView(array $posts, object $module): array
    {
        if (!$module instanceof \Modularity\Module\Posts\Posts) {
            return $posts;
        }

        $originalPosts = $module->data['posts'] ?? [];
        $originalStickyPosts = $module->data['stickyPosts'] ?? [];

        $module->data['posts'] = $posts;
        $module->data['stickyPosts'] = [];

        $controller = new \Modularity\Module\Posts\TemplateController\IndexTemplate($module);
        $preparedPosts = $controller->data['posts'] ?? [];

        $module->data['posts'] = $originalPosts;
        $module->data['stickyPosts'] = $originalStickyPosts;

        return is_array($preparedPosts) ? $preparedPosts : [];
    }

    private function getPostsPerPage(array $viewData): int
    {
        $postsCount = $viewData['posts_count'] ?? 3;

        if (!is_numeric($postsCount)) {
            return 3;
        }

        $postsCount = (int) $postsCount;

        return $postsCount > 0 ? min($postsCount, 100) : 3;
    }

    private function getCurrentPageNumber(array $viewData, object $module): int
    {
        $pageNumber = filter_input(INPUT_GET, $this->getPaginationQueryVarName($viewData, $module), FILTER_VALIDATE_INT);

        return is_int($pageNumber) && $pageNumber > 0 ? $pageNumber : 1;
    }

    private function getPaginationArguments(array $viewData, object $module, int $currentPage): ?array
    {
        if (($viewData['posts_pagination'] ?? null) !== 'page_numbers') {
            return null;
        }

        $maxNumPages = (int) ($viewData['maxNumPages'] ?? 0);

        if ($maxNumPages < 2) {
            return [];
        }

        $queryVarName = $this->getPaginationQueryVarName($viewData, $module);
        $listItemOne = [
            'href' => remove_query_arg($queryVarName),
            'label' => __('First page', 'modularity'),
        ];

        $listItems = array_map(
            static fn(int $pageNumber): array => [
                'href' => add_query_arg($queryVarName, $pageNumber),
                'label' => sprintf(__('Page %d', 'modularity'), $pageNumber),
            ],
            range(2, $maxNumPages)
        );

        return [
            'list' => array_merge([$listItemOne], $listItems),
            'current' => $currentPage,
            'linkPrefix' => $queryVarName,
        ];
    }

    private function getPaginationQueryVarName(array $viewData, object $module): string
    {
        $moduleId = $module->ID ?? $viewData['ID'] ?? '';

        return 'mod-posts-' . $moduleId . '-page';
    }

    private function classListContains(string $classList, string $className): bool
    {
        return in_array($className, preg_split('/\s+/', trim($classList)) ?: [], true);
    }
}
