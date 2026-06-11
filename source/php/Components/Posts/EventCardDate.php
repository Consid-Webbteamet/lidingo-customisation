<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\Posts;

use DateTimeImmutable;
use DateTimeInterface;
use Municipio\Controller\SingularEvent;
use Municipio\Helper\DateFormat;

class EventCardDate
{
    /** Resolve event card date data from schema schedules without timezone-shifting imported local times. */
    public static function resolve(object $post, bool $includeDateInTimeLabel = false): array
    {
        $selectedSchedule = self::getSelectedSchedule($post);
        $eventStartDate = self::getSchemaDateProperty($post, 'startDate');
        $selectedStartDate = self::getScheduleDateProperty($selectedSchedule, 'startDate') ?? $eventStartDate;
        $selectedEndDate = self::getScheduleDateProperty($selectedSchedule, 'endDate');
        $selectedStartDateLocal = self::toLocalDateTime($selectedStartDate);
        $selectedEndDateLocal = self::toLocalDateTime($selectedEndDate);

        return [
            'badgeDate' => self::formatDate($selectedStartDateLocal),
            'date' => self::formatDisplayDate($selectedStartDateLocal, $includeDateInTimeLabel),
            'dateTime' => self::formatDateTimeRange($selectedStartDateLocal, $selectedEndDateLocal),
            'time' => self::formatTimeRange($selectedStartDateLocal, $selectedEndDateLocal),
            'link' => self::buildLink($post, $selectedStartDateLocal),
        ];
    }

    private static function getSelectedSchedule(object $post): ?object
    {
        $schedules = self::getSchedules($post);
        $eventStartDate = self::getSchemaDateProperty($post, 'startDate');
        $eventStartDateKey = $eventStartDate instanceof DateTimeInterface
            ? $eventStartDate->format(SingularEvent::CURRENT_OCCASION_DATE_FORMAT)
            : null;

        if ($eventStartDateKey !== null) {
            foreach ($schedules as $schedule) {
                $scheduleStartDate = self::getScheduleDateProperty($schedule, 'startDate');

                if (
                    $scheduleStartDate instanceof DateTimeInterface
                    && $scheduleStartDate->format(SingularEvent::CURRENT_OCCASION_DATE_FORMAT) === $eventStartDateKey
                ) {
                    return $schedule;
                }
            }
        }

        return $schedules[0] ?? null;
    }

    private static function getSchedules(object $post): array
    {
        $eventSchedules = method_exists($post, 'getSchemaProperty')
            ? $post->getSchemaProperty('eventSchedule')
            : null;

        $eventSchedules = is_array($eventSchedules)
            ? $eventSchedules
            : (!empty($eventSchedules) ? [$eventSchedules] : []);

        usort(
            $eventSchedules,
            static fn($a, $b): int => (self::getScheduleDateProperty($a, 'startDate') ?? null) <=> (self::getScheduleDateProperty($b, 'startDate') ?? null)
        );

        return $eventSchedules;
    }

    private static function getSchemaDateProperty(object $post, string $property): ?DateTimeInterface
    {
        if (!method_exists($post, 'getSchemaProperty')) {
            return null;
        }

        $value = $post->getSchemaProperty($property);

        return $value instanceof DateTimeInterface ? $value : null;
    }

    private static function getScheduleDateProperty(?object $schedule, string $property): ?DateTimeInterface
    {
        if ($schedule === null || !method_exists($schedule, 'getProperty')) {
            return null;
        }

        $value = $schedule->getProperty($property);

        return $value instanceof DateTimeInterface ? $value : null;
    }

    private static function toLocalDateTime(?DateTimeInterface $date): ?DateTimeImmutable
    {
        if (!$date instanceof DateTimeInterface) {
            return null;
        }

        return DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m-d H:i:s'),
            wp_timezone()
        ) ?: null;
    }

    private static function buildLink(object $post, ?DateTimeInterface $startDate): string
    {
        $link = method_exists($post, 'getPermalink') ? (string) $post->getPermalink() : '';

        if (!$startDate instanceof DateTimeInterface) {
            return $link;
        }

        $separator = str_contains($link, '?') ? '&' : '?';

        return $link . $separator . SingularEvent::CURRENT_OCCASION_GET_PARAM . '=' . $startDate->format(SingularEvent::CURRENT_OCCASION_DATE_FORMAT);
    }

    private static function formatDate(?DateTimeInterface $date): ?string
    {
        return $date instanceof DateTimeInterface
            ? $date->format(DateFormat::getDateFormat('date'))
            : null;
    }

    private static function formatDisplayDate(?DateTimeInterface $date, bool $includeTime): ?string
    {
        if (!$date instanceof DateTimeInterface) {
            return null;
        }

        $format = $includeTime
            ? DateFormat::getDateFormat('date-time')
            : DateFormat::getDateFormat('date');

        return wp_date($format, $date->getTimestamp());
    }

    private static function formatTimeRange(?DateTimeInterface $startDate, ?DateTimeInterface $endDate): ?string
    {
        if (!$startDate instanceof DateTimeInterface) {
            return null;
        }

        $time = wp_date(DateFormat::getDateFormat('time'), $startDate->getTimestamp());

        if ($endDate instanceof DateTimeInterface) {
            $time .= ' - ' . wp_date(DateFormat::getDateFormat('time'), $endDate->getTimestamp());
        }

        return $time;
    }

    private static function formatDateTimeRange(?DateTimeInterface $startDate, ?DateTimeInterface $endDate): ?string
    {
        $dateTime = self::formatDisplayDate($startDate, true);

        if ($dateTime === null) {
            return null;
        }

        if ($endDate instanceof DateTimeInterface) {
            $dateTime .= ' - ' . wp_date(DateFormat::getDateFormat('time'), $endDate->getTimestamp());
        }

        return $dateTime;
    }
}
