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

        $itemId = (int) ($menuItem['id'] ?? 0);

        if ($itemId <= 0 || get_post_type($itemId) !== 'page') {
            return $fetchUrl;
        }

        $expectedDepth = count(get_post_ancestors($itemId)) + 2;

        if ($expectedDepth === $depth) {
            return $fetchUrl;
        }

        return add_query_arg('depth', (string) $expectedDepth, $fetchUrl);
    }
}
