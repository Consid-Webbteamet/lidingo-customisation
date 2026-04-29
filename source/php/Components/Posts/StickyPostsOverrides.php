<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\Posts;

class StickyPostsOverrides
{
    /** Register posts module sticky overrides. */
    public function addHooks(): void
    {
        add_filter('Modularity/Block/Data', [$this, 'disableStickyPresentation'], 70, 3);
    }

    /** Treat legacy sticky posts as regular posts in Modularity post lists. */
    public function disableStickyPresentation(array $viewData, array $block, object $module): array
    {
        unset($module);

        if (($block['name'] ?? '') !== 'acf/posts') {
            return $viewData;
        }

        $viewData['stickyPosts'] = [];

        if (!empty($viewData['posts']) && is_array($viewData['posts'])) {
            foreach ($viewData['posts'] as $post) {
                if (is_object($post) && property_exists($post, 'isSticky')) {
                    $post->isSticky = false;
                }
            }
        }

        return $viewData;
    }
}
