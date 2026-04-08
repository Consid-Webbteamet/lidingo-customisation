<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class OngoingWorkDateFields
{
    private const POST_TYPE = 'pagaende-arbeten';

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
                    'key' => 'field_lidingo_ongoing_work_start_date',
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
                    'key' => 'field_lidingo_ongoing_work_end_date',
                    'label' => __('Slutdatum', 'lidingo-customisation'),
                    'name' => 'ongoing_work_end_date',
                    'type' => 'date_picker',
                    'instructions' => __('Valfritt', 'lidingo-customisation'),
                    'required' => 0,
                    'display_format' => 'Y-m-d',
                    'return_format' => 'Ymd',
                    'first_day' => 1,
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
