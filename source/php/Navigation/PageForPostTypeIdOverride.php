<?php

declare(strict_types=1);

namespace LidingoCustomisation\Navigation;

class PageForPostTypeIdOverride
{
    private const NEWS_ARCHIVE_PAGE_ID = 258;

    /** Register navigation overrides for selected archive page mappings. */
    public function addHooks(): void
    {
        add_filter('Municipio/Navigation/PageForPostTypeId', [$this, 'disableNewsArchivePageTreeMapping'], 10, 2);
        add_filter('Municipio/Navigation/Items', [$this, 'preventNewsArchiveDrawerFallback'], 20, 2);
    }

    /** Keep the news archive page from acting as a post container in Municipio navigation. */
    public function disableNewsArchivePageTreeMapping(mixed $pageId, mixed $postType): mixed
    {
        return $postType === 'nyheter' ? 0 : $pageId;
    }

    /** Stop Municipio from falling back to unrelated branch items for the news archive drawer request. */
    public function preventNewsArchiveDrawerFallback(array $items, string $identifier): array
    {
        if (!$this->isNewsArchiveDrawerRequest($identifier)) {
            return $items;
        }

        return [];
    }

    private function isNewsArchiveDrawerRequest(string $identifier): bool
    {
        if ($identifier !== 'mobile') {
            return false;
        }

        if (!defined('REST_REQUEST') || REST_REQUEST !== true) {
            return false;
        }

        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $pageId = isset($_GET['pageId']) ? (int) $_GET['pageId'] : 0;

        return $pageId === self::NEWS_ARCHIVE_PAGE_ID
            && str_contains($requestUri, '/wp-json/municipio/v1/navigation/children/render');
    }
}
