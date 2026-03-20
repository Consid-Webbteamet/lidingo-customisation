<?php

declare(strict_types=1);

namespace LidingoCustomisation\Search;

use Municipio\PostObject\PostObjectInterface;

class SearchResultBuilder
{
    public function __construct(
        private SearchTextFormatter $textFormatter = new SearchTextFormatter()
    ) {
    }

    /** Transform post objects into the card data used by the search template. */
    public function buildResults(array $posts, array $searchTerms, array $typeGroups): array
    {
        $results = [];

        foreach ($posts as $post) {
            if (!$post instanceof PostObjectInterface) {
                continue;
            }

            $postType = $post->getPostType();
            $group = $this->getGroupForPostType($postType, $typeGroups);
            $groupLabel = $group['label'] ?? '';

            $results[] = [
                'title' => $this->textFormatter->highlightText($this->getPostTitle($post), $searchTerms),
                'excerpt' => $this->buildHighlightedExcerpt($post, $searchTerms),
                'permalink' => $this->getPostPermalink($post),
                'image' => $this->getSearchResultImage($post),
                'breadcrumbs' => $this->getResultBreadcrumbs($post, $groupLabel),
                'typeLabel' => $groupLabel,
            ];
        }

        return $results;
    }

    /** Find the configured type group for a given post type. */
    private function getGroupForPostType(string $postType, array $typeGroups): ?array
    {
        foreach ($typeGroups as $group) {
            if (in_array($postType, $group['post_types'], true)) {
                return $group;
            }
        }

        return null;
    }

    /** Resolve the best available title from the post object. */
    private function getPostTitle(PostObjectInterface $post): string
    {
        $title = $post->getTitle();

        if ($title !== '') {
            return $this->textFormatter->normalizeText($title);
        }

        return $this->textFormatter->normalizeText((string) ($post->postTitleFiltered ?? $post->postTitle ?? ''));
    }

    /** Resolve the best available permalink from the post object. */
    private function getPostPermalink(PostObjectInterface $post): string
    {
        $permalink = $post->getPermalink();

        if ($permalink !== '') {
            return $permalink;
        }

        return (string) ($post->permalink ?? '');
    }

    /** Build the visible excerpt for a single search result. */
    private function buildHighlightedExcerpt(PostObjectInterface $post, array $searchTerms): string
    {
        $excerpt = (string) ($post->excerpt ?? $post->getExcerpt() ?? '');
        $content = (string) ($post->postContentFiltered ?? $post->getContent() ?? $post->post_content ?? '');

        return $this->textFormatter->buildHighlightedExcerpt($excerpt, $content, $searchTerms);
    }

    /** Build breadcrumbs from post ancestors or the configured archive page. */
    private function getResultBreadcrumbs(PostObjectInterface $post, string $fallbackLabel): array
    {
        $postId = $post->getId();
        $postType = $post->getPostType();

        if ($postId <= 0 || $postType === '') {
            return $fallbackLabel !== '' ? [$fallbackLabel] : [];
        }

        $items = [];

        if (is_post_type_hierarchical($postType)) {
            $ancestorIds = array_reverse(get_post_ancestors($postId));

            foreach ($ancestorIds as $ancestorId) {
                $label = get_the_title($ancestorId);

                if (is_string($label) && $label !== '') {
                    $items[] = $label;
                }
            }
        } else {
            $archivePageId = $this->getArchivePageIdForPostType($postType);

            if ($archivePageId > 0) {
                $ancestorIds = array_reverse(get_post_ancestors($archivePageId));

                foreach ($ancestorIds as $ancestorId) {
                    $label = get_the_title($ancestorId);

                    if (is_string($label) && $label !== '') {
                        $items[] = $label;
                    }
                }

                $archiveLabel = get_the_title($archivePageId);

                if (is_string($archiveLabel) && $archiveLabel !== '') {
                    $items[] = $archiveLabel;
                }
            }
        }

        if (empty($items) && $fallbackLabel !== '') {
            $items[] = $fallbackLabel;
        }

        return array_slice(array_values(array_unique($items)), -3);
    }

    /** Extract image data for the search card when a result image exists. */
    private function getSearchResultImage(PostObjectInterface $post): ?array
    {
        $image = $post->getImage(620, 300);

        if ($image === null || !method_exists($image, 'getUrl')) {
            return null;
        }

        $url = (string) $image->getUrl();

        if ($url === '') {
            return null;
        }

        $alt = method_exists($image, 'getAltText')
            ? (string) $image->getAltText()
            : '';

        if ($alt === '') {
            $alt = $this->getPostTitle($post);
        }

        return [
            'url' => $url,
            'alt' => $alt,
        ];
    }

    /** Resolve the archive page option used for breadcrumb context. */
    private function getArchivePageIdForPostType(string $postType): int
    {
        if ($postType === 'post') {
            return (int) get_option('page_for_posts');
        }

        return (int) get_option('page_for_' . $postType);
    }
}
