<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class ServiceInfoCategoryIconFields
{
    private const FIELD_GROUP_KEY = 'group_lidingo_service_info_category_icon';
    private const LEGACY_FIELD_GROUP_KEY = 'group_695288c6283da';

    /** Register service category icon field hooks. */
    public function addHooks(): void
    {
        add_action('acf/init', [$this, 'registerFieldGroup']);
        add_filter('acf/load_field_group', [$this, 'hideLegacyFieldGroup']);
    }

    /** Replace the legacy ligature icon picker with an image selector. */
    public function registerFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => __('Service Information Category', 'lidingo-customisation'),
            'fields' => [
                [
                    'key' => 'field_lidingo_service_info_category_icon',
                    'label' => __('Ikon', 'lidingo-customisation'),
                    'name' => 'icon',
                    'type' => 'image',
                    'required' => 1,
                    'return_format' => 'id',
                    'preview_size' => 'thumbnail',
                    'library' => 'all',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => 'service_category',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'active' => 1,
            'description' => '',
            'show_in_rest' => 0,
        ]);
    }

    /** Hide the upstream field group to avoid duplicate icon selectors. */
    public function hideLegacyFieldGroup($fieldGroup)
    {
        if (($fieldGroup['key'] ?? '') === self::LEGACY_FIELD_GROUP_KEY) {
            return false;
        }

        return $fieldGroup;
    }
}
