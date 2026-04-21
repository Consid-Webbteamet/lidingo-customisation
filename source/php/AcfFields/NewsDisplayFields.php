<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class NewsDisplayFields
{
    private const NEWS_POST_TYPES = ['news', 'nyheter'];
    private const DISABLED_FIELD_NAMES = [
        'post_one_page_show_title',
        'post_table_of_contents',
    ];

    /** Register hooks that remove unused display fields for news. */
    public function addHooks(): void
    {
        foreach (self::DISABLED_FIELD_NAMES as $fieldName) {
            add_filter('acf/prepare_field/name=' . $fieldName, [$this, 'maybeHideField']);
            add_filter('acf/load_value/name=' . $fieldName, [$this, 'forceDisabledValue'], PHP_INT_MAX, 3);
            add_filter('acf/update_value/name=' . $fieldName, [$this, 'forceDisabledValue'], PHP_INT_MAX, 3);
        }
    }

    /** Hide the field in the news editor. */
    public function maybeHideField($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        return $this->isNewsEditorContext() ? false : $field;
    }

    /** Force the field value to stay disabled on news posts. */
    public function forceDisabledValue($value, $postId)
    {
        return $this->isNewsPostId($postId) ? false : $value;
    }

    private function isNewsEditorContext(): bool
    {
        $postType = $this->getCurrentEditorPostType();

        return is_string($postType) && in_array($postType, self::NEWS_POST_TYPES, true);
    }

    private function getCurrentEditorPostType(): ?string
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (is_object($screen) && isset($screen->post_type) && is_string($screen->post_type)) {
            return sanitize_key($screen->post_type);
        }

        $postId = $this->getEditorPostId();

        if ($postId > 0) {
            $postType = get_post_type($postId);

            return is_string($postType) ? sanitize_key($postType) : null;
        }

        if (isset($_GET['post_type']) && is_string($_GET['post_type'])) {
            return sanitize_key(wp_unslash($_GET['post_type']));
        }

        return null;
    }

    private function getEditorPostId(): int
    {
        if (isset($_GET['post']) && is_numeric($_GET['post'])) {
            return (int) $_GET['post'];
        }

        if (isset($_POST['post_ID']) && is_numeric($_POST['post_ID'])) {
            return (int) $_POST['post_ID'];
        }

        return 0;
    }

    private function isNewsPostId($postId): bool
    {
        if (!is_numeric($postId)) {
            return false;
        }

        $postType = get_post_type((int) $postId);

        return is_string($postType) && in_array(sanitize_key($postType), self::NEWS_POST_TYPES, true);
    }
}
