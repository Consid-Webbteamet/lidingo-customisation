<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

class ArchivePageFields
{
    private const FIELD_GROUP_KEY = 'group_lidingo_archive_page_settings';
    private const FIELD_KEY_BADGE_TAXONOMY = 'field_lidingo_archive_badge_taxonomy';
    private const FIELD_KEY_EVENT_HERO_CARD_SECTION = 'field_lidingo_event_archive_hero_card_section';
    private const FIELD_KEY_EVENT_HERO_CARD_SECTION_END = 'field_lidingo_event_archive_hero_card_section_end';
    private const DATE_BADGE_POST_TYPES = ['news', 'nyheter'];
    private const SERVICE_INFO_ARCHIVE_POST_TYPE = 'service_information';
    public const FIELD_NAME_BADGE_TAXONOMY = 'lidingo_archive_badge_taxonomy';
    public const FIELD_NAME_EVENT_HERO_CARD_TITLE = 'lidingo_event_archive_hero_card_title';
    public const FIELD_NAME_EVENT_HERO_CARD_LINK = 'lidingo_event_archive_hero_card_link';
    public const LOCATION_RULE_PARAM = 'lidingo_archive_page';
    public const LOCATION_RULE_VALUE = 'assigned_archive_page';
    private const EVENT_ARCHIVE_POST_TYPE = 'evenemang';

    /** Register archive page field hooks. */
    public function addHooks(): void
    {
        add_action('acf/init', [$this, 'registerFieldGroup']);
        add_filter('acf/load_field_group', [$this, 'maybeHideFieldGroup']);
        add_filter('acf/load_field/key=' . self::FIELD_KEY_BADGE_TAXONOMY, [$this, 'populateBadgeTaxonomyChoices']);
        add_filter('acf/prepare_field/key=' . self::FIELD_KEY_BADGE_TAXONOMY, [$this, 'maybeHideBadgeTaxonomyField']);
        add_filter('acf/prepare_field/key=' . self::FIELD_KEY_EVENT_HERO_CARD_SECTION, [$this, 'maybeHideEventArchiveField']);
        add_filter('acf/prepare_field/key=' . self::FIELD_KEY_EVENT_HERO_CARD_SECTION_END, [$this, 'maybeHideEventArchiveField']);
        add_filter('acf/prepare_field/name=' . self::FIELD_NAME_EVENT_HERO_CARD_TITLE, [$this, 'maybeHideEventArchiveField']);
        add_filter('acf/prepare_field/name=' . self::FIELD_NAME_EVENT_HERO_CARD_LINK, [$this, 'maybeHideEventArchiveField']);
        add_filter('acf/location/rule_types', [$this, 'registerLocationRuleType']);
        add_filter('acf/location/rule_values/' . self::LOCATION_RULE_PARAM, [$this, 'registerLocationRuleValues']);
        add_filter('acf/location/rule_match/' . self::LOCATION_RULE_PARAM, [$this, 'matchLocationRule'], 10, 3);
    }

    /** Register the archive settings field group. */
    public function registerFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => __('Arkivinställningar', 'lidingo-customisation'),
            'fields' => [
                [
                    'key' => self::FIELD_KEY_BADGE_TAXONOMY,
                    'label' => __('Badge-taxonomi', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_BADGE_TAXONOMY,
                    'type' => 'select',
                    'instructions' => __('Välj vilken taxonomi som ska visas som badge på korten för den här arkivsidan.', 'lidingo-customisation'),
                    'choices' => [],
                    'default_value' => '',
                    'allow_null' => 1,
                    'ui' => 1,
                    'multiple' => 0,
                    'return_format' => 'value',
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                ],
                [
                    'key' => self::FIELD_KEY_EVENT_HERO_CARD_SECTION,
                    'label' => __('Evenemang: Puff i header', 'lidingo-customisation'),
                    'name' => '',
                    'type' => 'accordion',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'open' => 1,
                    'multi_expand' => 0,
                    'endpoint' => 0,
                ],
                [
                    'key' => 'field_lidingo_event_archive_hero_card_title',
                    'label' => __('Rubrik', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_EVENT_HERO_CARD_TITLE,
                    'type' => 'text',
                    'instructions' => __('Visas som rubrik i kortet i headern på evenemangsarkivet.', 'lidingo-customisation'),
                    'required' => 0,
                    'placeholder' => __('Har du tips på evenemang?', 'lidingo-customisation'),
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                ],
                [
                    'key' => 'field_lidingo_event_archive_hero_card_link',
                    'label' => __('Knapplänk', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_EVENT_HERO_CARD_LINK,
                    'type' => 'link',
                    'instructions' => __('Visas som knapp i kortet i headern på evenemangsarkivet.', 'lidingo-customisation'),
                    'required' => 0,
                    'return_format' => 'array',
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                ],
                [
                    'key' => self::FIELD_KEY_EVENT_HERO_CARD_SECTION_END,
                    'label' => '',
                    'name' => '',
                    'type' => 'accordion',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'open' => 0,
                    'multi_expand' => 0,
                    'endpoint' => 1,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => self::LOCATION_RULE_PARAM,
                        'operator' => '==',
                        'value' => self::LOCATION_RULE_VALUE,
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'side',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => 1,
            'description' => '',
            'show_in_rest' => 0,
        ]);
    }

    /** Populate the badge taxonomy choices. */
    public function populateBadgeTaxonomyChoices(array $field): array
    {
        $field['choices'] = [];

        $pageId = $this->getCurrentPageId();

        if ($pageId <= 0) {
            return $field;
        }

        $postType = $this->getAssignedArchivePostType($pageId);

        if ($postType === null) {
            return $field;
        }

        $taxonomies = get_object_taxonomies($postType, 'objects');

        if (!is_array($taxonomies)) {
            return $field;
        }

        foreach ($taxonomies as $taxonomy) {
            if (!is_object($taxonomy) || empty($taxonomy->name) || !is_string($taxonomy->name)) {
                continue;
            }

            if (empty($taxonomy->public) && empty($taxonomy->show_ui)) {
                continue;
            }

            $field['choices'][$taxonomy->name] = !empty($taxonomy->labels->singular_name)
                ? (string) $taxonomy->labels->singular_name
                : $taxonomy->name;
        }

        return $field;
    }

    /** Hide the archive settings group when no field in it applies to the archive. */
    public function maybeHideFieldGroup($fieldGroup)
    {
        if (!is_array($fieldGroup) || ($fieldGroup['key'] ?? '') !== self::FIELD_GROUP_KEY) {
            return $fieldGroup;
        }

        $pageId = $this->getCurrentPageId();

        if ($pageId <= 0) {
            return $fieldGroup;
        }

        $postType = $this->getAssignedArchivePostType($pageId);

        if ($postType === null) {
            return false;
        }

        return in_array($postType, self::DATE_BADGE_POST_TYPES, true)
            || $postType === self::SERVICE_INFO_ARCHIVE_POST_TYPE
            ? false
            : $fieldGroup;
    }

    /** Hide the badge taxonomy field when the archive layout never uses taxonomy badges. */
    public function maybeHideBadgeTaxonomyField($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        $pageId = $this->getCurrentPageId();

        if ($pageId <= 0) {
            return false;
        }

        $postType = $this->getAssignedArchivePostType($pageId);

        if ($postType === null || $this->shouldHideBadgeTaxonomyField($postType)) {
            return false;
        }

        return $field;
    }

    /** Hide event-only archive fields outside the event archive page. */
    public function maybeHideEventArchiveField($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        $pageId = $this->getCurrentPageId();

        if ($pageId <= 0) {
            return false;
        }

        return $this->getAssignedArchivePostType($pageId) === self::EVENT_ARCHIVE_POST_TYPE
            ? $field
            : false;
    }

    /** Determine whether the badge taxonomy field is relevant for the archive. */
    private function shouldHideBadgeTaxonomyField(string $postType): bool
    {
        if (in_array($postType, self::DATE_BADGE_POST_TYPES, true)) {
            return true;
        }

        if ($postType === self::SERVICE_INFO_ARCHIVE_POST_TYPE) {
            return true;
        }

        if ($postType !== self::EVENT_ARCHIVE_POST_TYPE) {
            return false;
        }

        return get_theme_mod('archive_' . $postType . '_style') === 'schema';
    }

    /** Register the custom ACF location rule type. */
    public function registerLocationRuleType(array $choices): array
    {
        $group = __('Page', 'acf');
        $choices[$group] = is_array($choices[$group] ?? null) ? $choices[$group] : [];
        $choices[$group][self::LOCATION_RULE_PARAM] = __('Assigned archive page', 'lidingo-customisation');

        return $choices;
    }

    /** Register the custom ACF location rule value. */
    public function registerLocationRuleValues(array $choices): array
    {
        $choices[self::LOCATION_RULE_VALUE] = __('Assigned archive page', 'lidingo-customisation');

        return $choices;
    }

    /** Match the custom location rule. */
    public function matchLocationRule(bool $match, array $rule, array $options): bool
    {
        if (!$this->isPageEditScreen($options)) {
            return $rule['operator'] === '!=';
        }

        $postId = isset($options['post_id']) && is_numeric($options['post_id'])
            ? (int) $options['post_id']
            : 0;

        if ($postId <= 0) {
            $postId = $this->getCurrentPageId();
        }

        $isAssignedArchivePage = $this->getAssignedArchivePostType($postId) !== null;

        return $rule['operator'] === '!='
            ? !$isAssignedArchivePage
            : $isAssignedArchivePage;
    }

    private function isPageEditScreen(array $options): bool
    {
        if (isset($options['post_type']) && $options['post_type'] !== 'page') {
            return false;
        }

        if (isset($options['post_id']) && !is_numeric($options['post_id'])) {
            return false;
        }

        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();

            if (is_object($screen) && property_exists($screen, 'base') && $screen->base !== 'post') {
                return false;
            }

            if (is_object($screen) && property_exists($screen, 'post_type') && !empty($screen->post_type) && $screen->post_type !== 'page') {
                return false;
            }
        }

        return true;
    }

    private function getCurrentPageId(): int
    {
        $postId = filter_input(INPUT_GET, 'post', FILTER_VALIDATE_INT);

        if (is_int($postId) && $postId > 0) {
            return $postId;
        }

        $postId = filter_input(INPUT_POST, 'post_ID', FILTER_VALIDATE_INT);

        if (is_int($postId) && $postId > 0) {
            return $postId;
        }

        global $post;

        return is_object($post) && !empty($post->ID) ? (int) $post->ID : 0;
    }

    private function getAssignedArchivePostType(int $pageId): ?string
    {
        $postTypes = get_post_types(
            [
                'public' => true,
                'publicly_queryable' => true,
            ],
            'objects'
        );

        if (!is_array($postTypes)) {
            return null;
        }

        foreach ($postTypes as $postType) {
            if (!is_object($postType) || empty($postType->name) || !is_string($postType->name)) {
                continue;
            }

            if (empty($postType->has_archive) || in_array($postType->name, ['attachment', 'page'], true)) {
                continue;
            }

            $assignedPageId = get_option('page_for_' . $postType->name);

            if (is_numeric($assignedPageId) && (int) $assignedPageId === $pageId) {
                return sanitize_key($postType->name);
            }
        }

        return null;
    }
}
