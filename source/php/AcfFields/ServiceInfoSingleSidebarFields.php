<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

use LidingoCustomisation\Helper\ServiceInfoStatus;

class ServiceInfoSingleSidebarFields
{
    public const FIELD_GROUP_KEY = 'group_lidingo_service_info_single_sidebar';
    public const FIELD_NAME_HEADING = 'lidingo_service_info_sidebar_heading';
    public const FIELD_NAME_SUBHEADING = 'lidingo_service_info_sidebar_subheading';
    public const FIELD_NAME_RELATED_POSTS = 'lidingo_service_info_sidebar_related_posts';
    private const POST_TYPE = 'service_information';

    /** Register field group and relationship query filters. */
    public function addHooks(): void
    {
        add_action('acf/init', [$this, 'registerFieldGroup']);
        add_filter(
            'acf/fields/relationship/query/name=' . self::FIELD_NAME_RELATED_POSTS,
            [$this, 'filterRelatedPostsQuery'],
            10,
            3
        );
    }

    /** Register the sidebar field group for service information singles. */
    public function registerFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => __('Driftinformation: sidokolumn', 'lidingo-customisation'),
            'fields' => [
                [
                    'key' => 'field_lidingo_service_info_sidebar_heading',
                    'label' => __('Rubrik', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_HEADING,
                    'type' => 'text',
                    'required' => 0,
                    'placeholder' => __('Övrig driftinformation', 'lidingo-customisation'),
                ],
                [
                    'key' => 'field_lidingo_service_info_sidebar_subheading',
                    'label' => __('Underrubrik', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_SUBHEADING,
                    'type' => 'text',
                    'required' => 0,
                    'placeholder' => __('Planerade arbeten', 'lidingo-customisation'),
                ],
                [
                    'key' => 'field_lidingo_service_info_sidebar_related_posts',
                    'label' => __('Visa driftärenden', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_RELATED_POSTS,
                    'type' => 'relationship',
                    'required' => 0,
                    'post_type' => [self::POST_TYPE],
                    'filters' => ['search'],
                    'return_format' => 'id',
                    'elements' => [],
                    'instructions' => __(
                        'Välj aktuella eller planerade driftärenden. Avslutade döljs automatiskt i sidokolumnen.',
                        'lidingo-customisation'
                    ),
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
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => 1,
            'show_in_rest' => 0,
        ]);
    }

    /** Limit relationship picker to current and planned posts. */
    public function filterRelatedPostsQuery(array $args, array $field, mixed $postId): array
    {
        $allowedPostIds = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => ['publish', 'future'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'OR',
                ServiceInfoStatus::getCurrentMetaQuery(),
                ServiceInfoStatus::getPlannedMetaQuery(),
            ],
        ]);

        $args['post_type'] = [self::POST_TYPE];
        $args['post_status'] = ['publish', 'future'];
        $args['post__in'] = !empty($allowedPostIds) ? array_map('intval', $allowedPostIds) : [0];

        unset($args['meta_query']);

        return $args;
    }
}
