<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\Posts;

class PostsDateOverrides
{
    /** Register the archive date format override. */
    public function addHooks(): void
    {
        add_filter('Municipio/PostObject/getArchiveDateFormat', [$this, 'overrideArchiveDateFormat'], 20, 2);
    }

    /** Prefer the site's date format for archive timestamps. */
    public function overrideArchiveDateFormat(string $format, object $post): string
    {
        unset($post);

        $dateFormat = (string) get_option('date_format', 'Y-m-d');
        $timeFormat = (string) get_option('time_format', 'H:i');

        $resolvedDateTimeFormat = trim($dateFormat . ' ' . $timeFormat);

        return match ($format) {
            'date-time', 'time', $timeFormat, $resolvedDateTimeFormat => $dateFormat,
            default => $format,
        };
    }
}
