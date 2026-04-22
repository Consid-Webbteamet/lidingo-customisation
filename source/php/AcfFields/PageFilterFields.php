<?php

declare(strict_types=1);

namespace LidingoCustomisation\AcfFields;

use LidingoCustomisation\Templates\PageFilterTemplate;

class PageFilterFields
{
    private const FIELD_GROUP_KEY = 'group_lidingo_page_filter_settings';
    private const FIELD_KEY_SHOW_SEARCH = 'field_lidingo_page_filter_show_search';
    private const FIELD_KEY_TAXONOMIES = 'field_lidingo_page_filter_taxonomies';

    public const FIELD_NAME_SHOW_SEARCH = 'lidingo_page_filter_show_search';
    public const FIELD_NAME_TAXONOMIES = 'lidingo_page_filter_taxonomies';

    /** Register page filter field hooks. */
    public function addHooks(): void
    {
        add_action('init', [$this, 'registerPageFilterTaxonomies'], 20);
        add_action('acf/init', [$this, 'registerFieldGroup']);
        add_filter('acf/load_field_group', [$this, 'maybeHideFieldGroup']);
        add_filter('acf/load_field/key=' . self::FIELD_KEY_TAXONOMIES, [$this, 'populateTaxonomyChoices']);
    }

    /** Register and enable taxonomies used by page filter lists. */
    public function registerPageFilterTaxonomies(): void
    {
        $this->registerPageFilterTaxonomy(
            'omrade',
            __('Område', 'lidingo-customisation'),
            __('Områden', 'lidingo-customisation')
        );
        $this->registerPageFilterTaxonomy(
            'skolform',
            __('Skolform', 'lidingo-customisation'),
            __('Skolformer', 'lidingo-customisation')
        );
    }

    /** Register one hierarchical taxonomy for pages. */
    private function registerPageFilterTaxonomy(string $taxonomy, string $singularName, string $pluralName): void
    {
        register_taxonomy($taxonomy, ['page'], [
            'labels' => [
                'name' => $pluralName,
                'singular_name' => $singularName,
                'search_items' => sprintf(__('Sök %s', 'lidingo-customisation'), strtolower($pluralName)),
                'all_items' => sprintf(__('Alla %s', 'lidingo-customisation'), strtolower($pluralName)),
                'parent_item' => sprintf(__('Överordnad %s', 'lidingo-customisation'), strtolower($singularName)),
                'parent_item_colon' => sprintf(__('Överordnad %s:', 'lidingo-customisation'), strtolower($singularName)),
                'edit_item' => sprintf(__('Redigera %s', 'lidingo-customisation'), strtolower($singularName)),
                'update_item' => sprintf(__('Uppdatera %s', 'lidingo-customisation'), strtolower($singularName)),
                'add_new_item' => sprintf(__('Lägg till %s', 'lidingo-customisation'), strtolower($singularName)),
                'new_item_name' => sprintf(__('Namn på ny %s', 'lidingo-customisation'), strtolower($singularName)),
                'menu_name' => $pluralName,
            ],
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'show_tagcloud' => false,
            'rewrite' => false,
        ]);
    }

    /** Register the page filter settings field group. */
    public function registerFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => __('Filterlista', 'lidingo-customisation'),
            'fields' => [
                [
                    'key' => self::FIELD_KEY_SHOW_SEARCH,
                    'label' => __('Visa sökfält', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_SHOW_SEARCH,
                    'type' => 'true_false',
                    'instructions' => __('Sökningen filtrerar undersidor på titel och innehåll.', 'lidingo-customisation'),
                    'default_value' => 1,
                    'ui' => 1,
                    'ui_on_text' => __('Ja', 'lidingo-customisation'),
                    'ui_off_text' => __('Nej', 'lidingo-customisation'),
                ],
                [
                    'key' => self::FIELD_KEY_TAXONOMIES,
                    'label' => __('Filtertaxonomier', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_TAXONOMIES,
                    'type' => 'select',
                    'instructions' => __('Välj taxonomier kopplade till sidor som ska visas som filter.', 'lidingo-customisation'),
                    'choices' => [],
                    'default_value' => [],
                    'allow_null' => 0,
                    'ui' => 1,
                    'multiple' => 1,
                    'return_format' => 'value',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
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

    /** Hide the field group unless the current page uses the page filter template. */
    public function maybeHideFieldGroup($fieldGroup)
    {
        if (($fieldGroup['key'] ?? '') !== self::FIELD_GROUP_KEY) {
            return $fieldGroup;
        }

        return $this->isPageFilterTemplate($this->getCurrentPageId())
            ? $fieldGroup
            : false;
    }

    /** Populate page taxonomy choices. */
    public function populateTaxonomyChoices(array $field): array
    {
        $field['choices'] = [];

        $taxonomies = get_object_taxonomies('page', 'objects');

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

    /** Resolve the current page ID from the editor request context. */
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

    /** Check whether a page uses the filter template. */
    private function isPageFilterTemplate(int $pageId): bool
    {
        return $pageId > 0 && get_page_template_slug($pageId) === PageFilterTemplate::TEMPLATE_SLUG;
    }
}
