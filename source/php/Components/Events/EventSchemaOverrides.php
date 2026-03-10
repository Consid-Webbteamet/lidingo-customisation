<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\Events;

class EventSchemaOverrides
{
    public function addHooks(): void
    {
        add_filter('acf/update_value/name=schemaData', [$this, 'normalizeManualEventSchemaData'], 200, 4);
        add_filter('Municipio/Template/single/viewData', [$this, 'injectSchemaDescriptionIntoSingleEventView'], 20, 2);
    }

    public function normalizeManualEventSchemaData(mixed $value, string|int $postId, array $field, mixed $original): mixed
    {
        unset($field, $original);

        if (!is_numeric($postId) || !is_array($value) || ($value['@type'] ?? null) !== 'Event') {
            return $value;
        }

        $value = $this->addFallbackEventSchedule($value);
        $this->syncStartDateMeta((int) $postId, $value);

        return $value;
    }

    public function injectSchemaDescriptionIntoSingleEventView(array $data, string $postType): array
    {
        unset($postType);

        if (!$this->isSingleSchemaEventView($data)) {
            return $data;
        }

        if (!empty($data['scheduleDescription']) || empty($data['description'])) {
            return $data;
        }

        $data['scheduleDescription'] = $data['description'];

        return $data;
    }

    private function isSingleSchemaEventView(array $data): bool
    {
        if (!is_single() || empty($data['post']) || !is_object($data['post']) || !method_exists($data['post'], 'getSchema')) {
            return false;
        }

        return $data['post']->getSchema()->getType() === 'Event';
    }

    private function addFallbackEventSchedule(array $schemaData): array
    {
        if (!empty($schemaData['eventSchedule']) || empty($schemaData['startDate'])) {
            return $schemaData;
        }

        $endDate = $schemaData['endDate'] ?? $schemaData['startDate'];

        $schemaData['eventSchedule'] = [[
            '@type' => 'Schedule',
            'startDate' => $schemaData['startDate'],
            'endDate' => $endDate,
        ]];

        if (empty($schemaData['endDate'])) {
            $schemaData['endDate'] = $endDate;
        }

        return $schemaData;
    }

    private function syncStartDateMeta(int $postId, array $schemaData): void
    {
        $startDate = $schemaData['startDate'] ?? null;
        $formattedStartDate = $this->formatDateForMeta($startDate);

        if ($formattedStartDate === null) {
            delete_post_meta($postId, 'startDate');
            return;
        }

        update_post_meta($postId, 'startDate', $formattedStartDate);
    }

    private function formatDateForMeta(mixed $dateValue): ?string
    {
        if (!is_string($dateValue) || $dateValue === '') {
            return null;
        }

        $timestamp = strtotime($dateValue);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
