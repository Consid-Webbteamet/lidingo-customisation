<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class ModularityTocFields
{
    private const DISABLED_FIELD_KEYS = [
        'field_6942556c3aa83',
        'field_694255833aa84',
    ];

    public function addHooks(): void
    {
        foreach (self::DISABLED_FIELD_KEYS as $fieldKey) {
            add_filter('acf/prepare_field/key=' . $fieldKey, [$this, 'hideField']);
            add_filter('acf/load_value/key=' . $fieldKey, [$this, 'forceDisabledValue'], PHP_INT_MAX);
            add_filter('acf/update_value/key=' . $fieldKey, [$this, 'forceDisabledValue'], PHP_INT_MAX);
        }
    }

    public function hideField($field)
    {
        return false;
    }

    public function forceDisabledValue(): bool
    {
        return false;
    }
}
