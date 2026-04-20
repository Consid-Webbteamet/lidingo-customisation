<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class ServiceInfoArchivePageFields
{
    public const FIELD_GROUP_KEY = 'group_lidingo_service_info_archive_page_settings';
    public const FIELD_NAME_EXTERNAL_SECTION_TITLE = 'lidingo_service_info_external_section_title';
    public const FIELD_NAME_EXTERNAL_ITEMS = 'lidingo_service_info_external_items';

    /** Register service info archive page field hooks. */
    public function addHooks(): void
    {
        add_action('acf/init', [$this, 'registerFieldGroup']);
        add_filter('acf/load_field_group', [$this, 'maybeHideFieldGroup']);
    }

    /** Register the service info archive settings field group. */
    public function registerFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => __('Driftinformation: externa aktörer', 'lidingo-customisation'),
            'fields' => [
                [
                    'key' => 'field_lidingo_service_info_external_section_title',
                    'label' => __('Rubrik för externa aktörer', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_EXTERNAL_SECTION_TITLE,
                    'type' => 'text',
                    'required' => 0,
                    'default_value' => __('Driftstörningar i system som sköts av andra aktörer', 'lidingo-customisation'),
                ],
                [
                    'key' => 'field_lidingo_service_info_external_items',
                    'label' => __('Externa aktörer', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_EXTERNAL_ITEMS,
                    'type' => 'repeater',
                    'button_label' => __('Lägg till aktör', 'lidingo-customisation'),
                    'layout' => 'row',
                    'sub_fields' => [
                        [
                            'key' => 'field_lidingo_service_info_external_title',
                            'label' => __('Titel', 'lidingo-customisation'),
                            'name' => 'title',
                            'type' => 'text',
                            'required' => 1,
                        ],
                        [
                            'key' => 'field_lidingo_service_info_external_description',
                            'label' => __('Beskrivning', 'lidingo-customisation'),
                            'name' => 'description',
                            'type' => 'textarea',
                            'required' => 0,
                            'rows' => 3,
                            'new_lines' => 'br',
                        ],
                        [
                            'key' => 'field_lidingo_service_info_external_url',
                            'label' => __('Extern länk', 'lidingo-customisation'),
                            'name' => 'url',
                            'type' => 'url',
                            'required' => 1,
                        ],
                        [
                            'key' => 'field_lidingo_service_info_external_icon',
                            'label' => __('Ikon', 'lidingo-customisation'),
                            'name' => 'icon',
                            'type' => 'image',
                            'required' => 1,
                            'return_format' => 'id',
                            'preview_size' => 'thumbnail',
                            'library' => 'all',
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => 1,
            'description' => '',
            'show_in_rest' => 0,
        ]);
    }

    /** Hide the field group unless service info applies. */
    public function maybeHideFieldGroup($fieldGroup)
    {
        if (($fieldGroup['key'] ?? '') !== self::FIELD_GROUP_KEY) {
            return $fieldGroup;
        }

        return $this->isServiceInfoPage($this->getCurrentPageId())
            ? $fieldGroup
            : false;
    }

    /** Resolve the current page ID from the editor request context. */
    private function getCurrentPageId(): int
    {
        $postId = filter_input(INPUT_GET, 'post', FILTER_VALIDATE_INT);

        if (is_int($postId) && $postId > 0) {
            return $postId;
        }

        $postId = filter_input(INPUT_POST, 'post_ID', FILTER_VALIDATE_INT);

        if (is_int($postId) && $postId > 0) {
            return $postId;
        }

        global $post;

        return is_object($post) && !empty($post->ID) ? (int) $post->ID : 0;
    }

    /** Find the post type assigned to the current archive page. */
    private function getAssignedArchivePostType(int $pageId): ?string
    {
        if ($pageId <= 0) {
            return null;
        }

        $postTypes = get_post_types(
            [
                'public' => true,
                'publicly_queryable' => true,
            ],
            'objects'
        );

        if (!is_array($postTypes)) {
            return null;
        }

        foreach ($postTypes as $postType) {
            if (!is_object($postType) || empty($postType->name) || !is_string($postType->name)) {
                continue;
            }

            if (empty($postType->has_archive) || in_array($postType->name, ['attachment', 'page'], true)) {
                continue;
            }

            $assignedPageId = get_option('page_for_' . $postType->name);

            if (is_numeric($assignedPageId) && (int) $assignedPageId === $pageId) {
                return sanitize_key($postType->name);
            }
        }

        return null;
    }

    /** Check whether the current page is the service info archive page. */
    private function isServiceInfoPage(int $pageId): bool
    {
        if ($pageId <= 0) {
            return false;
        }

        if ($this->getAssignedArchivePostType($pageId) === 'service_information') {
            return true;
        }

        return (int) get_field('service_information_page', 'service-information-settings') === $pageId;
    }
}
