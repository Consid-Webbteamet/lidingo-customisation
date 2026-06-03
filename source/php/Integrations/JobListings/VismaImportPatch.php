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
                    $this->replaceImportCallbacks($function[0]);
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
        $item->hasExpired = $this->hasDateExpired($publishEndDate) ? '1' : '0';
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
        $endOfDayTimestamp = $this->getEndOfDayTimestamp($date);
        if ($endOfDayTimestamp === null) {
            return 0;
        }

        return max(0, (int) floor(($endOfDayTimestamp - time()) / DAY_IN_SECONDS));
    }

    private function hasDateExpired(string $date): bool
    {
        $endOfDayTimestamp = $this->getEndOfDayTimestamp($date);

        return $endOfDayTimestamp === null || time() > $endOfDayTimestamp;
    }

    private function getEndOfDayTimestamp(string $date): ?int
    {
        if ($date === '') {
            return null;
        }

        $dateTime = date_create_immutable($date, wp_timezone());
        if (!$dateTime instanceof \DateTimeImmutable) {
            return null;
        }

        return $dateTime->setTime(23, 59, 59)->getTimestamp();
    }

    private function replaceImportCallbacks(\JobListings\Cron\VismaImport $importer): void
    {
        remove_action($importer->getHookName(), [$importer, 'importXml']);
        add_action($importer->getHookName(), fn() => $this->importXml($importer));

        remove_action('admin_init', [$importer, 'importXmlTrigger']);
        add_action('admin_init', fn() => $this->importXmlTrigger($importer));
    }

    public function importXmlTrigger(\JobListings\Cron\VismaImport $importer): void
    {
        $triggerKey = str_replace('\\', '', get_class($importer));
        if (!isset($_GET[$triggerKey])) {
            return;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        $this->importXml($importer);

        wp_die(
            sprintf(
                __('%sImport done%s %s Data has been imported with %s method. %s', 'job-listings'),
                '<h1>',
                '</h1>',
                '<p>',
                '<strong>' . get_class($importer) . '</strong>',
                '</p>'
            ),
            'Import',
            ['back_link' => true]
        );
    }

    public function importXml(\JobListings\Cron\VismaImport $importer): ?bool
    {
        ini_set('max_execution_time', '600');

        $curl = new \JobListings\Helper\Curl(true, (int) $importer->cacheTTL);
        $data = $curl->request(
            (string) $importer->curlMethod,
            (string) $importer->baseUrl,
            (array) $importer->queryParams
        );

        $data = str_replace('&', '&amp;', (string) $data);

        try {
            $data = simplexml_load_string($data);
        } catch (\Exception $e) {
            if (!strstr($e->getMessage(), 'XML')) {
                throw $e;
            }
        }

        if (!is_object($data)) {
            if (defined('WP_CLI') && WP_CLI) {
                $errorString = 'Could not load XML at ' . home_url() . ': ' . $importer->baseUrl;
                error_log($errorString);
                \WP_CLI::warning($errorString);
            }

            return null;
        }

        $items = $data->xpath($importer->baseNode . '/' . $importer->subNode);
        if (empty($items)) {
            return null;
        }

        foreach ($items as $item) {
            $item = apply_filters(str_replace('\\', '/', get_class($importer)) . '/Item', $item);

            if ($item) {
                $this->updateItem($importer, $item);
            }
        }

        $importer->deactivateMissingJobs();

        return true;
    }

    private function updateItem(\JobListings\Cron\VismaImport $importer, $item): bool
    {
        if (!is_object($item) || empty($item)) {
            return false;
        }

        $dataObject = [];

        foreach ($importer->metaKeyMap() as $key => $target) {
            if ($key === 'contact') {
                continue;
            }

            if (!is_array($target)) {
                $dataObject[$key] = $target;
                continue;
            }

            $dataObject[$key] = $this->getMappedValue($importer, $item, $key, $target);
        }

        $dataObject['contact'] = $this->contactsByGuid[trim((string) ($item->Guid ?? ''))] ?? [];

        $uuid = $this->normalizeScalar($dataObject['uuid'] ?? '');
        if ($uuid !== '' && is_numeric($uuid)) {
            $importer->importedUuids[] = (int) $uuid;
        }

        $postObject = $importer->getPost([
            'key' => 'uuid',
            'value' => $dataObject['uuid'] ?? '',
        ]);

        if (!isset($postObject->ID)) {
            $postId = wp_insert_post([
                'post_title' => $this->normalizeScalar($dataObject['post_title'] ?? ''),
                'post_content' => $this->normalizeScalar($dataObject['post_content'] ?? ''),
                'post_type' => $importer->postType,
                'post_status' => 'publish',
            ]);
        } else {
            $postId = $postObject->ID;
            $updateDiff = [
                $postObject->post_title,
                $postObject->post_content,
                $this->normalizeScalar($dataObject['post_title'] ?? ''),
                $this->normalizeScalar($dataObject['post_content'] ?? ''),
            ];

            if (count(array_unique($updateDiff)) !== (count($updateDiff) / 2)) {
                wp_update_post([
                    'ID' => $postId,
                    'post_title' => $this->normalizeScalar($dataObject['post_title'] ?? ''),
                    'post_content' => $this->normalizeScalar($dataObject['post_content'] ?? ''),
                ]);
            }
        }

        $this->updateTaxonomy($postId, 'occupationclassifications', 'job-listing-category', $dataObject);
        $this->updateTaxonomy($postId, 'source_system', 'job-listing-source', $dataObject);
        $importer->updatePostMeta($postId, $dataObject);

        return true;
    }

    /** @param array<int, string> $target */
    private function getMappedValue(\JobListings\Cron\VismaImport $importer, $item, string $key, array $target)
    {
        if (count($target) === 5 && in_array($key, ['occupationclassifications', 'departments'], true)) {
            return $this->getClassificationNameByLevel(
                $this->getXmlValueByPath($item, array_slice($target, 0, 4)),
                $key === 'departments' ? 2 : 1
            );
        }

        return $this->normalizeMappedValue($this->getXmlValueByPath($item, $target));
    }

    /** @param array<int, string> $path */
    private function getXmlValueByPath($value, array $path)
    {
        $current = $value;

        foreach ($path as $segment) {
            if ($segment === '@attributes') {
                $current = $current instanceof SimpleXMLElement ? $current->attributes() : null;
                continue;
            }

            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }

            if (is_object($current) && isset($current->{$segment})) {
                $current = $current->{$segment};
                continue;
            }

            return '';
        }

        return $current;
    }

    private function normalizeMappedValue($value)
    {
        if ($value instanceof SimpleXMLElement) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            return array_map([$this, 'normalizeMappedValue'], $value);
        }

        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }

        if (is_object($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function getClassificationNameByLevel($nodes, int $level): string
    {
        if ($nodes instanceof SimpleXMLElement) {
            foreach ($nodes as $node) {
                if ((int) ($node->Level ?? 0) === $level) {
                    return trim((string) ($node->Name ?? ''));
                }
            }

            if ((int) ($nodes->Level ?? 0) === $level) {
                return trim((string) ($nodes->Name ?? ''));
            }
        }

        return '';
    }

    private function updateTaxonomy(int $postId, string $termSourceKey, string $termId, array $dataObject)
    {
        if (!isset($dataObject[$termSourceKey]) || empty($dataObject[$termSourceKey])) {
            return false;
        }

        $termValue = $this->stringifyTermValue($dataObject[$termSourceKey]);
        if ($termValue === '') {
            return false;
        }

        $termValue = ucfirst(str_replace(', ', ' - ', $termValue));
        $term = term_exists($termValue, $termId);

        if (is_null($term)) {
            $term = wp_insert_term(
                $termValue,
                $termId,
                [
                    'description' => $termValue,
                    'slug' => sanitize_title($termValue),
                ]
            );
        }

        wp_delete_object_term_relationships($postId, $termId);

        return wp_set_post_terms($postId, $termValue, $termId, true);
    }

    private function stringifyTermValue($value): string
    {
        if ($value instanceof SimpleXMLElement) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            $parts = [];

            array_walk_recursive(
                $value,
                static function ($part) use (&$parts): void {
                    if (!is_scalar($part) && !$part instanceof SimpleXMLElement) {
                        return;
                    }

                    $part = trim((string) $part);
                    if ($part !== '') {
                        $parts[] = $part;
                    }
                }
            );

            return implode(', ', array_values(array_unique($parts)));
        }

        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }

        return '';
    }

    private function normalizeScalar($value): string
    {
        return is_scalar($value) || $value === null ? trim((string) $value) : '';
    }
}
