<?php

declare(strict_types=1);

namespace LidingoCustomisation\Admin;

use WP_Post;

class StickyPostMetaBox
{
    private const NONCE_ACTION = 'lidingo_customisation_sticky_post';
    private const NONCE_NAME = '_lidingo_customisation_sticky_post_nonce';
    private const OPTION_PREFIX = 'sticky_post';

    public function addHooks(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'saveMetaBox']);
    }

    public function addMetaBox(string $postType): void
    {
        if (!$this->isSupportedPostType($postType) || !use_block_editor_for_post_type($postType)) {
            return;
        }

        add_meta_box(
            'lidingo-customisation-sticky-post',
            __('Pinna post', 'lidingo-customisation'),
            [$this, 'renderMetaBox'],
            $postType,
            'side',
            'high'
        );
    }

    public function renderMetaBox(WP_Post $post): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        printf(
            '<p><label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label></p><p class="description">%4$s</p>',
            esc_attr($this->getOptionKey($post->post_type)),
            checked(array_key_exists($post->ID, $this->getStickyOption($post->post_type)), true, false),
            esc_html__('Visa först i listor', 'lidingo-customisation'),
            esc_html__('Pinnat innehåll visas före övriga inlägg i Modularitys postlistor där samma posttyp används.', 'lidingo-customisation')
        );
    }

    public function saveMetaBox(int $postId): void
    {
        if (
            wp_is_post_autosave($postId)
            || wp_is_post_revision($postId)
            || !current_user_can('edit_post', $postId)
            || empty($_POST[self::NONCE_NAME])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            return;
        }

        $postType = get_post_type($postId);

        if (!is_string($postType) || !$this->isSupportedPostType($postType)) {
            return;
        }

        $optionKey = $this->getOptionKey($postType);
        $stickyOption = $this->getStickyOption($postType);

        if (!empty($_POST[$optionKey])) {
            $stickyOption[$postId] = $postId;
        } else {
            unset($stickyOption[$postId]);
        }

        update_option($optionKey, $stickyOption);
    }

    private function isSupportedPostType(string $postType): bool
    {
        $postTypeObject = get_post_type_object($postType);

        return $postTypeObject !== null && (bool) $postTypeObject->show_ui;
    }

    private function getStickyOption(string $postType): array
    {
        $option = get_option($this->getOptionKey($postType), []);

        return is_array($option) ? $option : [];
    }

    private function getOptionKey(string $postType): string
    {
        return self::OPTION_PREFIX . '_' . $postType;
    }
}
