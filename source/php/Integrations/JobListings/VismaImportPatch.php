<?php

declare(strict_types=1);

namespace LidingoCustomisation\Integrations\JobListings;

use SimpleXMLElement;

class VismaImportPatch
{
    private const ITEM_FILTER = 'JobListings/Cron/VismaImport/Item';
    private const DETAILS_URL = 'https://recruit.visma.com/External/Feeds/AssignmentItem.ashx';

    /** @var array<string, array<int, array<string, string>>> */
    private array $contactsByGuid = [];

    /** @var array<string, array<string, mixed>> */
    private array $settingsByGuidGroup = [];

    public function addHooks(): void
    {
        add_action('init', [$this, 'replaceVismaNormalizer'], 20);
        add_filter('add_post_metadata', [$this, 'replaceAddedContactMeta'], 10, 5);
        add_filter('pre_update_post_metadata', [$this, 'replaceContactMeta'], 10, 5);
    }

    public function replaceVismaNormalizer(): void
    {
        $this->settingsByGuidGroup = $this->getVismaImporterSettings();

        global $wp_filter;

        if (!isset($wp_filter[self::ITEM_FILTER])) {
            return;
        }

        foreach ($wp_filter[self::ITEM_FILTER]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;

                if (
                    is_array($function) &&
                    isset($function[0], $function[1]) &&
                    is_object($function[0]) &&
                    $function[0] instanceof \JobListings\Cron\VismaImport &&
                    $function[1] === 'normalize'
                ) {
                    remove_filter(self::ITEM_FILTER, $function, (int) $priority);
                }
            }
        }

        add_filter(self::ITEM_FILTER, [$this, 'normalize'], 10, 1);
    }

    public function normalize($item)
    {
        if (!$item instanceof SimpleXMLElement) {
            return $item;
        }

        $guid = trim((string) ($item->Guid ?? ''));
        $settings = $this->getSettingsForItem($item);

        $this->setDateValue($item, 'PublishStartDate');
        $this->setDateValue($item, 'PublishEndDate');
        $this->setDateValue($item, 'ApplicationEndDate');
        $this->setDateValue($item, 'EmploymentStartDate');
        $this->setDateValue($item, 'EmploymentEndDate');
        $this->setDateValue($item, 'Modified');

        $publishEndDate = trim((string) ($item->PublishEndDate ?? ''));
        $item->hasExpired = $publishEndDate !== '' && strtotime($publishEndDate) >= time() ? '0' : '1';
        $item->numberOfDaysLeft = $this->getDaysLeft(trim((string) ($item->ApplicationEndDate ?? '')));

        if (!empty($settings['apply_base_link'])) {
            $item->ReadMoreUrl = (string) $settings['apply_base_link'] . $guid;
        }

        $details = $this->getDetails($guid, $settings);
        $assignmentLoc = $this->getAssignmentLoc($details);

        if (isset($item->Localization->AssignmentLoc)) {
            $item->Localization->AssignmentLoc->WorkDescr = $this->buildWorkDescription($item, $assignmentLoc);
        }

        $this->contactsByGuid[$guid] = $this->getContacts($assignmentLoc);

        return $item;
    }

    public function replaceContactMeta($check, int $objectId, string $metaKey, $metaValue, $prevValue)
    {
        if (!$this->shouldReplaceContactMeta($objectId, $metaKey)) {
            return $check;
        }

        $guid = (string) get_post_meta($objectId, 'guid', true);
        $this->saveContactMeta($objectId, $metaKey, $this->contactsByGuid[$guid], $prevValue);

        return true;
    }

    public function replaceAddedContactMeta($check, int $objectId, string $metaKey, $metaValue, bool $unique)
    {
        if (!$this->shouldReplaceContactMeta($objectId, $metaKey)) {
            return $check;
        }

        $guid = (string) get_post_meta($objectId, 'guid', true);
        $this->saveContactMeta($objectId, $metaKey, $this->contactsByGuid[$guid], '');

        return true;
    }

    private function shouldReplaceContactMeta(int $objectId, string $metaKey): bool
    {
        if ($metaKey !== 'contact') {
            return false;
        }

        $guid = (string) get_post_meta($objectId, 'guid', true);

        return $guid !== '' && array_key_exists($guid, $this->contactsByGuid);
    }

    /**
     * Save contact meta while temporarily bypassing these short-circuit filters.
     *
     * @param array<int, array<string, string>> $contacts
     */
    private function saveContactMeta(int $objectId, string $metaKey, array $contacts, $prevValue): void
    {
        remove_filter('add_post_metadata', [$this, 'replaceAddedContactMeta'], 10);
        remove_filter('pre_update_post_metadata', [$this, 'replaceContactMeta'], 10);
        update_post_meta($objectId, $metaKey, $contacts, $prevValue);
        add_filter('add_post_metadata', [$this, 'replaceAddedContactMeta'], 10, 5);
        add_filter('pre_update_post_metadata', [$this, 'replaceContactMeta'], 10, 5);
    }

    /** @return array<string, array<string, mixed>> */
    private function getVismaImporterSettings(): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $importers = get_field('job_listings_importers', 'option');
        if (!is_array($importers)) {
            return [];
        }

        $settings = [];
        foreach ($importers as $importer) {
            if (($importer['acf_fc_layout'] ?? '') !== 'visma' || empty($importer['guidGroup'])) {
                continue;
            }

            $settings[(string) $importer['guidGroup']] = $importer;
        }

        return $settings;
    }

    /** @return array<string, mixed> */
    private function getSettingsForItem(SimpleXMLElement $item): array
    {
        if (count($this->settingsByGuidGroup) === 1) {
            return reset($this->settingsByGuidGroup) ?: [];
        }

        $readMoreUrl = (string) ($item->ReadMoreUrl ?? '');
        foreach ($this->settingsByGuidGroup as $guidGroup => $settings) {
            if ($readMoreUrl !== '' && str_contains($readMoreUrl, $guidGroup)) {
                return $settings;
            }
        }

        return reset($this->settingsByGuidGroup) ?: [];
    }

    private function getDetails(string $guid, array $settings): ?SimpleXMLElement
    {
        $guidGroup = trim((string) ($settings['guidGroup'] ?? ''));
        if ($guid === '' || $guidGroup === '' || !class_exists(\JobListings\Helper\Curl::class)) {
            return null;
        }

        $curl = new \JobListings\Helper\Curl(true, 60 * 60);
        $data = $curl->request('GET', self::DETAILS_URL, [
            'guidGroup' => $guidGroup,
            'guidAssignment' => $guid,
        ]);

        if (!is_string($data) || trim($data) === '') {
            return null;
        }

        $xml = simplexml_load_string($data);

        return $xml instanceof SimpleXMLElement ? $xml : null;
    }

    private function getAssignmentLoc(?SimpleXMLElement $details): ?SimpleXMLElement
    {
        if (
            !$details instanceof SimpleXMLElement ||
            !isset($details->Assignment->Localization->AssignmentLoc) ||
            !$details->Assignment->Localization->AssignmentLoc instanceof SimpleXMLElement
        ) {
            return null;
        }

        return $details->Assignment->Localization->AssignmentLoc;
    }

    private function buildWorkDescription(SimpleXMLElement $item, ?SimpleXMLElement $assignmentLoc): string
    {
        $fallbackWorkDescription = isset($item->Localization->AssignmentLoc->WorkDescr)
            ? trim((string) $item->Localization->AssignmentLoc->WorkDescr)
            : '';

        if (!$assignmentLoc instanceof SimpleXMLElement) {
            return $fallbackWorkDescription;
        }

        $content = [];
        $departmentDescription = trim((string) ($assignmentLoc->DepartmentDescr ?? ''));
        $workDescription = trim((string) ($assignmentLoc->WorkDescr ?? $fallbackWorkDescription));
        $qualifications = trim((string) ($assignmentLoc->Qualifications ?? ''));
        $additionalInfo = trim((string) ($assignmentLoc->AdditionalInfo ?? ''));

        if ($departmentDescription !== '') {
            $content[] = $departmentDescription;
        }

        $content[] = PHP_EOL . PHP_EOL . '  <!--more-->' . PHP_EOL . PHP_EOL;

        if ($workDescription !== '') {
            $content[] = '<h2>' . __('Work Description', 'job-listings') . '</h2>';
            $content[] = $workDescription;
        }

        if ($qualifications !== '') {
            $content[] = '<h2>' . __('Qualifications', 'job-listings') . '</h2>';
            $content[] = $qualifications;
        }

        if ($additionalInfo !== '') {
            $content[] = '<h2>' . __('Other', 'job-listings') . '</h2>';
            $content[] = $additionalInfo;
        }

        return implode("\r\n", $content);
    }

    /** @return array<int, array<string, string>> */
    private function getContacts(?SimpleXMLElement $assignmentLoc): array
    {
        if (
            !$assignmentLoc instanceof SimpleXMLElement ||
            !isset($assignmentLoc->ContactPersons->ContactPerson)
        ) {
            return [];
        }

        $contacts = [];
        foreach ($assignmentLoc->ContactPersons->ContactPerson as $contactNode) {
            $name = trim((string) ($contactNode->ContactName ?? ''));
            $phone = trim((string) ($contactNode->Telephone ?? ''));
            $cellphone = trim((string) ($contactNode->Cellphone ?? ''));
            $position = trim((string) ($contactNode->Title ?? ''));
            $email = trim((string) ($contactNode->Email ?? ''));

            if ($phone === '' && $cellphone !== '') {
                $phone = $cellphone;
            }

            if ($name === '' && $phone === '' && $position === '' && $email === '') {
                continue;
            }

            $contacts[] = [
                'name' => $name,
                'phone' => $phone,
                'phone_sanitized' => preg_replace('/\D/', '', $phone),
                'position' => $position,
                'email' => strtolower($email),
            ];
        }

        return $contacts;
    }

    private function setDateValue(SimpleXMLElement $item, string $key): void
    {
        if (!isset($item->{$key})) {
            return;
        }

        $timestamp = strtotime((string) $item->{$key});
        if ($timestamp === false) {
            return;
        }

        $item->{$key} = date('Y-m-d', $timestamp);
    }

    private function getDaysLeft(string $date): int
    {
        if ($date === '') {
            return 0;
        }

        $applicationEndDate = date_create($date);
        if ($applicationEndDate === false) {
            return 0;
        }

        return (int) date_diff(date_create(date('Y-m-d')), $applicationEndDate)->days;
    }
}
