<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class OngoingWorkDateFields
{
    private const POST_TYPE = 'pagaende-arbeten';
    private const FIELD_KEY_START_DATE = 'field_lidingo_ongoing_work_start_date';
    private const FIELD_KEY_END_DATE = 'field_lidingo_ongoing_work_end_date';
    private const FIELD_KEY_HAS_END_TIME = 'field_lidingo_ongoing_work_has_end_time';

    /** Register ongoing work date field hooks. */
    public function addHooks(): void
    {
        add_action('acf/init', [$this, 'registerFieldGroup']);
    }

    /** Register the ongoing work date fields. */
    public function registerFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_lidingo_ongoing_work_dates',
            'title' => __('Datum', 'lidingo-customisation'),
            'fields' => [
                [
                    'key' => self::FIELD_KEY_START_DATE,
                    'label' => __('Startdatum', 'lidingo-customisation'),
                    'name' => 'ongoing_work_start_date',
                    'type' => 'date_picker',
                    'instructions' => __('Välj startdatum. På sidan visas bara månad och år.', 'lidingo-customisation'),
                    'required' => 1,
                    'display_format' => 'Y-m-d',
                    'return_format' => 'Ymd',
                    'first_day' => 1,
                ],
                [
                    'key' => self::FIELD_KEY_END_DATE,
                    'label' => __('Slutdatum', 'lidingo-customisation'),
                    'name' => 'ongoing_work_end_date',
                    'type' => 'date_picker',
                    'instructions' => __('Om "Ändra status till slutförd automatiskt?" är aktiverat ändras status till Slutförd när slutdatumet har passerat. Om bara datum anges sker ändringen först när dagen har passerat.', 'lidingo-customisation'),
                    'required' => 0,
                    'display_format' => 'Y-m-d',
                    'return_format' => 'Ymd',
                    'first_day' => 1,
                ],
                [
                    'key' => self::FIELD_KEY_HAS_END_TIME,
                    'label' => __('Ändra status till slutförd automatiskt?', 'lidingo-customisation'),
                    'name' => 'ongoing_work_has_end_time',
                    'type' => 'true_false',
                    'instructions' => __('Kommer ändra status till Slutförd när slutdatum har paserat.', 'lidingo-customisation'),
                    'required' => 0,
                    'ui' => 1,
                    'default_value' => 0,
                    'conditional_logic' => [
                        [
                            [
                                'field' => self::FIELD_KEY_END_DATE,
                                'operator' => '!=empty',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'field_lidingo_ongoing_work_end_date_time',
                    'label' => __('Slutfört datum och tid', 'lidingo-customisation'),
                    'name' => 'ongoing_work_end_date_time',
                    'type' => 'date_time_picker',
                    'instructions' => __('Valfritt. Om du anger en tid här ändras status till Slutförd automatiskt vid den tidpunkten. Annars sker ändringen först när slutdatumet har passerat.', 'lidingo-customisation'),
                    'required' => 0,
                    'display_format' => 'Y-m-d H:i',
                    'return_format' => 'Y-m-d H:i:s',
                    'first_day' => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field' => self::FIELD_KEY_END_DATE,
                                'operator' => '!=empty',
                            ],
                            [
                                'field' => self::FIELD_KEY_HAS_END_TIME,
                                'operator' => '==',
                                'value' => '1',
                            ],
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => self::POST_TYPE,
                    ],
                ],
            ],
            'position' => 'acf_after_title',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
        ]);
    }
}
