<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\Posts;

class PostsDateOverrides
{
    /** Register archive date overrides. */
    public function addHooks(): void
    {
        add_filter('Municipio/PostObject/getArchiveDateFormat', [$this, 'overrideArchiveDateFormat'], 20, 2);
        add_filter('Municipio/PostObject/getArchiveDateTimestamp', [$this, 'overrideArchiveDateTimestamp'], 20, 2);
        add_filter('Municipio/PostObject/getPublishedTime', [$this, 'overridePublishedTime'], 20, 3);
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

    /**
     * Use Visma publish date for job listings in archive cards.
     */
    public function overrideArchiveDateTimestamp(?int $timestamp, object $post): ?int
    {
        $resolvedTimestamp = $this->resolveJobListingPublishTimestamp($post);

        return $resolvedTimestamp ?? $timestamp;
    }

    /** Keep post_date-based date sources aligned with Visma publish date for job listings. */
    public function overridePublishedTime(int $timestamp, object $post, bool $gmt): int
    {
        unset($gmt);

        $resolvedTimestamp = $this->resolveJobListingPublishTimestamp($post);

        return $resolvedTimestamp ?? $timestamp;
    }

    /** Resolve the Visma publish start date from job listing meta as a Unix timestamp. */
    private function resolveJobListingPublishTimestamp(object $post): ?int
    {
        if (!method_exists($post, 'getPostType') || !method_exists($post, 'getId')) {
            return null;
        }

        if ($post->getPostType() !== 'job-listing') {
            return null;
        }

        $postId = (int) $post->getId();

        if ($postId <= 0) {
            return null;
        }

        $publishStartDate = trim((string) get_post_meta($postId, 'publish_start_date', true));

        if ($publishStartDate === '') {
            return null;
        }

        if (ctype_digit($publishStartDate)) {
            $resolvedTimestamp = (int) $publishStartDate;

            return $resolvedTimestamp > 0 ? $resolvedTimestamp : null;
        }

        $dateTime = date_create_immutable($publishStartDate, wp_timezone());

        if (!$dateTime instanceof \DateTimeImmutable) {
            return null;
        }

        $resolvedTimestamp = $dateTime->getTimestamp();

        return $resolvedTimestamp > 0 ? $resolvedTimestamp : null;
    }
}
