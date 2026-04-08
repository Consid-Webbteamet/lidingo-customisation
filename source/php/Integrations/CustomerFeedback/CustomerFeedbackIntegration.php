<?php

declare(strict_types=1);

namespace LidingoCustomisation\Integrations\CustomerFeedback;

use CustomerFeedback\Responses;
use WP_Post;

class CustomerFeedbackIntegration
{
    private const FIELD_GROUP_KEY = 'group_lidingo_customer_feedback_settings';
    private const FIELD_KEY_ENABLED = 'field_lidingo_customer_feedback_enabled';
    private const FIELD_NAME_ENABLED = 'lidingo_customer_feedback_enabled';
    private const SUMMARY_META_BOX_ID = 'lidingo-customer-feedback-summary';
    private const MANAGED_POST_TYPES = ['page', 'post'];

    /** Register customer feedback hooks. */
    public function addHooks(): void
    {
        add_filter('CustomerFeedback/post_types', [$this, 'filterAllowedPostTypes'], 20, 1);
        add_action('acf/init', [$this, 'registerFieldGroup']);
        add_action('add_meta_boxes', [$this, 'addSummaryMetaBox'], 20, 2);
    }

    /** Register the feedback toggle field group. */
    public function registerFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => self::FIELD_GROUP_KEY,
            'title' => __('Kundkommentarer', 'lidingo-customisation'),
            'fields' => [
                [
                    'key' => self::FIELD_KEY_ENABLED,
                    'label' => __('Visa feedback', 'lidingo-customisation'),
                    'name' => self::FIELD_NAME_ENABLED,
                    'type' => 'true_false',
                    'required' => 0,
                    'default_value' => 0,
                    'ui' => 1,
                    'message' => __('Visa feedback för detta innehåll', 'lidingo-customisation'),
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
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
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
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

    /** Limit customer feedback to managed post types. */
    public function filterAllowedPostTypes(mixed $allowedPostTypes): array
    {
        $normalizedPostTypes = $this->normalizePostTypes($allowedPostTypes);
        $contextPost = $this->getContextPost();

        if (!$contextPost instanceof WP_Post || !$this->isManagedPostType($contextPost->post_type)) {
            return $normalizedPostTypes;
        }

        $normalizedPostTypes = array_values(array_diff($normalizedPostTypes, self::MANAGED_POST_TYPES));

        if ($this->isEnabledForPost((int) $contextPost->ID)) {
            $normalizedPostTypes[] = $contextPost->post_type;
        }

        if (is_admin() && empty($normalizedPostTypes)) {
            $normalizedPostTypes[] = '__lidingo_customer_feedback_none__';
        }

        return array_values(array_unique($normalizedPostTypes));
    }

    /** Add the feedback summary meta box. */
    public function addSummaryMetaBox(string $postType, ?WP_Post $post = null): void
    {
        if (!$this->isManagedPostType($postType)) {
            return;
        }

        remove_meta_box('customer-feedback-summary-meta', $postType, 'side');

        if (!$post instanceof WP_Post || !$this->isEnabledForPost((int) $post->ID)) {
            return;
        }

        add_meta_box(
            self::SUMMARY_META_BOX_ID,
            sprintf(
                '%s (%s %s)',
                __('Customer feedback summary', 'customer-feedback'),
                __('since', 'customer-feedback'),
                get_the_modified_date('Y-m-d H:i', $post)
            ),
            [$this, 'renderSummaryMetaBox'],
            $postType,
            'side',
            'default'
        );
    }

    /** Render the feedback summary table. */
    public function renderSummaryMetaBox(WP_Post $post): void
    {
        if (!class_exists(Responses::class)) {
            echo esc_html__('Customer Feedback plugin is not available.', 'lidingo-customisation');
            return;
        }

        $results = Responses::getResponses((int) $post->ID);
        $totalCount = array_sum($results);

        echo '<table id="customer-feedback-summary" cellspacing="0" cellpadding="0"><tbody>';

        foreach ($results as $answer => $count) {
            $label = match ($answer) {
                'yes' => '<span style="color:#30BA41;">' . esc_html__('Positive', 'customer-feedback') . '</span>',
                'no' => '<span style="color:#BA3030;">' . esc_html__('Negative', 'customer-feedback') . '</span>',
                default => null,
            };

            if ($label === null) {
                continue;
            }

            $percent = $totalCount > 0
                ? (int) round(($count / $totalCount) * 100)
                : 0;

            printf(
                '<tr><td>%s</td><td>%d%% (%d)</td></tr>',
                $label,
                $percent,
                (int) $count
            );
        }

        echo '</tbody></table>';

        echo '<div style="padding:12px 14px;">';
        echo esc_html__(
            'The feedback stats will be reset to zero (without removing the actual feedback messages) when a new version of the post is saved.',
            'customer-feedback'
        );
        echo '</div>';
    }

    private function getContextPost(): ?WP_Post
    {
        if (is_admin()) {
            $postId = $this->getAdminPostId();

            if ($postId > 0) {
                $post = get_post($postId);

                return $post instanceof WP_Post ? $post : null;
            }

            return null;
        }

        if (!is_singular()) {
            return null;
        }

        $post = get_post(get_queried_object_id());

        return $post instanceof WP_Post ? $post : null;
    }

    private function getAdminPostId(): int
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

        return $post instanceof WP_Post ? (int) $post->ID : 0;
    }

    private function isManagedPostType(?string $postType): bool
    {
        return is_string($postType) && in_array($postType, self::MANAGED_POST_TYPES, true);
    }

    private function isEnabledForPost(int $postId): bool
    {
        if ($postId <= 0) {
            return false;
        }

        if (function_exists('get_field')) {
            return (bool) get_field(self::FIELD_NAME_ENABLED, $postId);
        }

        return (bool) get_post_meta($postId, self::FIELD_NAME_ENABLED, true);
    }

    private function normalizePostTypes(mixed $allowedPostTypes): array
    {
        if (empty($allowedPostTypes)) {
            return [];
        }

        if (is_string($allowedPostTypes)) {
            $allowedPostTypes = [$allowedPostTypes];
        }

        if (!is_array($allowedPostTypes)) {
            $allowedPostTypes = (array) $allowedPostTypes;
        }

        return array_values(array_filter(array_map(
            static fn (mixed $postType): string => is_string($postType) ? $postType : '',
            $allowedPostTypes
        )));
    }
}
