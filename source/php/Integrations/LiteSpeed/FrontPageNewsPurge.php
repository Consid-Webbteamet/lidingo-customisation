<?php

declare(strict_types=1);

namespace LidingoCustomisation\Integrations\LiteSpeed;

use WP_Post;

class FrontPageNewsPurge
{
    private const POST_TYPE = 'nyheter';

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

        $this->purgeFrontPage();
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
