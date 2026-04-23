<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

class PageTemplatePostTypes
{
    private const FALLBACK_POST_TYPES = ['page'];
    private const EXCLUDED_POST_TYPES = ['attachment'];

    public static function get(): array
    {
        $defaultPostTypes = self::getDefaultPostTypes();
        $postTypes = apply_filters(
            'lidingo_customisation/page_template_post_types',
            $defaultPostTypes
        );

        if (!is_array($postTypes)) {
            return $defaultPostTypes;
        }

        $postTypes = array_values(array_filter(
            array_map(static fn($postType): string => is_string($postType) ? sanitize_key($postType) : '', $postTypes),
            static fn(string $postType): bool => $postType !== ''
        ));

        return !empty($postTypes) ? $postTypes : $defaultPostTypes;
    }

    private static function getDefaultPostTypes(): array
    {
        $postTypes = get_post_types(
            [
                'public' => true,
                'show_ui' => true,
                'hierarchical' => true,
            ],
            'objects'
        );

        if (!is_array($postTypes)) {
            return self::FALLBACK_POST_TYPES;
        }

        $postTypes = array_values(array_filter(
            array_map(
                static function ($postType): string {
                    if (!is_object($postType) || empty($postType->name) || !is_string($postType->name)) {
                        return '';
                    }

                    if (in_array($postType->name, self::EXCLUDED_POST_TYPES, true)) {
                        return '';
                    }

                    return sanitize_key($postType->name);
                },
                $postTypes
            ),
            static fn(string $postType): bool => $postType !== ''
        ));

        return !empty($postTypes) ? $postTypes : self::FALLBACK_POST_TYPES;
    }
}
