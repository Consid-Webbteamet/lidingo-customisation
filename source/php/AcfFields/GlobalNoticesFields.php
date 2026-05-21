<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class GlobalNoticesFields
{
    private const LOCATION_FIELD_KEY = 'field_6798fa40c712f';
    private const ALLOWED_LOCATIONS = ['banner', 'content'];
    private const DEFAULT_LOCATION = 'banner';
    private const TYPE_FIELD_KEY = 'field_6798fa82cc9ba';
    private const ALLOWED_TYPES = ['warning', 'danger'];
    private const DEFAULT_TYPE = 'warning';

    /** Register hooks that constrain unsupported global notice options. */
    public function addHooks(): void
    {
        add_filter('acf/load_field/key=' . self::LOCATION_FIELD_KEY, [$this, 'filterLocationField']);
        add_filter('acf/load_value/key=' . self::LOCATION_FIELD_KEY, [$this, 'normalizeLocation'], 20, 3);
        add_filter('acf/update_value/key=' . self::LOCATION_FIELD_KEY, [$this, 'normalizeLocation'], 20, 3);
        add_filter('acf/load_field/key=' . self::TYPE_FIELD_KEY, [$this, 'filterTypeField']);
        add_filter('acf/load_value/key=' . self::TYPE_FIELD_KEY, [$this, 'normalizeType'], 20, 3);
        add_filter('acf/update_value/key=' . self::TYPE_FIELD_KEY, [$this, 'normalizeType'], 20, 3);
    }

    /** Limit global notice locations to banner and content. */
    public function filterLocationField($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        $choices = is_array($field['choices'] ?? null) ? $field['choices'] : [];
        $field['choices'] = array_intersect_key($choices, array_flip(self::ALLOWED_LOCATIONS));
        $field['default_value'] = self::DEFAULT_LOCATION;

        return $field;
    }

    /** Normalize legacy or unsupported global notice locations. */
    public function normalizeLocation($value)
    {
        if (!is_string($value)) {
            return self::DEFAULT_LOCATION;
        }

        $value = sanitize_key($value);

        return in_array($value, self::ALLOWED_LOCATIONS, true)
            ? $value
            : self::DEFAULT_LOCATION;
    }

    /** Limit global notice levels to warning and danger. */
    public function filterTypeField($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        $choices = is_array($field['choices'] ?? null) ? $field['choices'] : [];
        $field['choices'] = array_intersect_key($choices, array_flip(self::ALLOWED_TYPES));
        $field['default_value'] = self::DEFAULT_TYPE;

        return $field;
    }

    /** Normalize legacy or unsupported global notice levels. */
    public function normalizeType($value)
    {
        if (!is_string($value)) {
            return self::DEFAULT_TYPE;
        }

        $value = sanitize_key($value);

        return in_array($value, self::ALLOWED_TYPES, true)
            ? $value
            : self::DEFAULT_TYPE;
    }
}
