<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class HeroFields
{
    private const HERO_FIELD_GROUP_KEY = 'group_614b3f1a751bf';
    private const HIDDEN_FIELD_NAMES = [
        'mod_hero_display_as',
        'mod_hero_background_color',
        'mod_hero_text_color',
        'mod_hero_byline',
        'mod_hero_meta',
        'mod_hero_body',
        'mod_hero_media_first',
        'mod_hero_buttons',
    ];
    private const DEFAULT_DISPLAY_AS = 'default';

    /** Register hero field hooks. */
    public function addHooks(): void
    {
        foreach (self::HIDDEN_FIELD_NAMES as $fieldName) {
            add_filter('acf/prepare_field/name=' . $fieldName, [$this, 'hideField']);
        }

        add_filter('acf/load_value/name=mod_hero_display_as', [$this, 'forceDefaultDisplayAs'], 10, 3);
    }

    /** Hide unsupported hero fields. */
    public function hideField($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        if (($field['parent'] ?? '') !== self::HERO_FIELD_GROUP_KEY) {
            return $field;
        }

        return false;
    }

    /** Force the default hero display mode. */
    public function forceDefaultDisplayAs($value, $postId, array $field): string
    {
        if (($field['parent'] ?? '') !== self::HERO_FIELD_GROUP_KEY) {
            return is_string($value) ? $value : self::DEFAULT_DISPLAY_AS;
        }

        return self::DEFAULT_DISPLAY_AS;
    }
}
