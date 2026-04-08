<?php

declare(strict_types=1);

namespace LidingoCustomisation\Helper;

class ServiceInfoStatus
{
    public const STATUS_CURRENT = 'current';
    public const STATUS_PLANNED = 'planned';
    public const STATUS_COMPLETED = 'completed';

    private const POST_TYPE = 'service_information';
    private const START_DATE_META_KEY = 'start_date';
    private const END_DATE_META_KEY = 'end_date';

    /** Return the number of active service info posts. */
    public static function getCurrentCount(): int
    {
        $query = new \WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'meta_query' => self::getCurrentMetaQuery(),
        ]);

        return (int) $query->found_posts;
    }

    /** Build the active service info meta query. */
    public static function getCurrentMetaQuery(?string $now = null): array
    {
        $now = $now ?: current_time('mysql');

        return [
            'relation' => 'AND',
            [
                'key' => self::START_DATE_META_KEY,
                'value' => $now,
                'compare' => '<=',
                'type' => 'DATETIME',
            ],
            [
                'relation' => 'OR',
                [
                    'key' => self::END_DATE_META_KEY,
                    'value' => $now,
                    'compare' => '>=',
                    'type' => 'DATETIME',
                ],
                [
                    'key' => self::END_DATE_META_KEY,
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => self::END_DATE_META_KEY,
                    'value' => '',
                    'compare' => '=',
                ],
            ],
        ];
    }

    /** Build the planned service info meta query. */
    public static function getPlannedMetaQuery(?string $now = null): array
    {
        $now = $now ?: current_time('mysql');

        return [
            [
                'key' => self::START_DATE_META_KEY,
                'value' => $now,
                'compare' => '>',
                'type' => 'DATETIME',
            ],
        ];
    }

    /** Build the completed service info meta query. */
    public static function getCompletedMetaQuery(?string $now = null): array
    {
        $now = $now ?: current_time('mysql');

        return [
            'relation' => 'AND',
            [
                'key' => self::END_DATE_META_KEY,
                'value' => '',
                'compare' => '!=',
            ],
            [
                'key' => self::END_DATE_META_KEY,
                'value' => $now,
                'compare' => '<',
                'type' => 'DATETIME',
            ],
        ];
    }
}
