<?php

declare(strict_types=1);

namespace LidingoCustomisation\Navigation;

class PageTreeFetchDepth
{
    /** Register fetch URL corrections for the drawer page tree. */
    public function addHooks(): void
    {
        add_filter('Municipio/Navigation/PageTree/FetchUrl', [$this, 'correctDrawerPageTreeFetchDepth'], 20, 4);
    }

    /**
     * Keep async drawer child requests aligned with the rendered page tree depth.
     *
     * Municipio uses the identifier "mobile" for the drawer page tree, regardless
     * of whether the drawer is currently shown on a desktop or mobile viewport.
     */
    public function correctDrawerPageTreeFetchDepth(string $fetchUrl, array $menuItem, string $identifier, int $depth): string
    {
        if ($identifier !== 'mobile') {
            return $fetchUrl;
        }

        $expectedDepth = $this->getExpectedDepth((int) ($menuItem['id'] ?? 0));

        if ($expectedDepth === null || $expectedDepth === $depth) {
            return $fetchUrl;
        }

        return add_query_arg('depth', (string) $expectedDepth, $fetchUrl);
    }

    /**
     * Post type items are rendered below their archive page (page_for_{post type}),
     * so their depth continues from that page rather than from the tree root.
     */
    private function getExpectedDepth(int $itemId): ?int
    {
        if ($itemId <= 0) {
            return null;
        }

        $postType = get_post_type($itemId);

        if (!$postType) {
            return null;
        }

        $itemAncestors = count(get_post_ancestors($itemId));

        if ($postType === 'page') {
            return $itemAncestors + 2;
        }

        $archivePageId = (int) get_option("page_for_{$postType}");

        if ($archivePageId <= 0) {
            return null;
        }

        return $itemAncestors + count(get_post_ancestors($archivePageId)) + 3;
    }
}
