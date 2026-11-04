<?php

declare(strict_types=1);

namespace LidingoCustomisation\Integrations\LiteSpeed;

use WP_Post;

class FrontPageNewsPurge
{
    private const POST_TYPE = 'nyheter';
    private const FRAGMENT_CACHE_GROUP = 'modules';

    /** Register LiteSpeed purge hooks for front page news changes. */
    public function addHooks(): void
    {
        add_action('transition_post_status', [$this, 'purgeFrontPageWhenNewsChanges'], 20, 3);
    }

    /** Purge the front page when a public news item is published, updated, or unpublished. */
    public function purgeFrontPageWhenNewsChanges(string $newStatus, string $oldStatus, WP_Post $post): void
    {
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) {
            return;
        }

        if ($newStatus !== 'publish' && $oldStatus !== 'publish') {
            return;
        }

        $this->clearFrontPageFragmentCache();
        $this->purgeFrontPage();
    }

    /** Clear Modularity's block fragments so the purged front page is not rebuilt with old news. */
    private function clearFrontPageFragmentCache(): void
    {
        $group = is_multisite()
            ? self::FRAGMENT_CACHE_GROUP . '-' . get_current_blog_id()
            : self::FRAGMENT_CACHE_GROUP;

        wp_cache_delete((int) get_option('page_on_front'), $group);
    }

    /** Purge the site's front page cache when LiteSpeed Cache is available. */
    private function purgeFrontPage(): void
    {
        if (!has_action('litespeed_purge_url')) {
            return;
        }

        do_action('litespeed_purge_url', home_url('/'), false, true);
    }
}
