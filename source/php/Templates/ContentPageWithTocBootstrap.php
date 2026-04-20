<?php

declare(strict_types=1);

namespace LidingoCustomisation\Templates;

use WP_Post;
class ContentPageWithTocBootstrap
{
    private const BOOTSTRAP_COMPLETE_META_KEY = '_lidingo_content_page_toc_bootstrap_complete';
    private const DESKTOP_MODULE_ID_META_KEY = '_lidingo_content_page_toc_desktop_module_id';
    private const AUTO_MODULE_META_KEY = '_lidingo_content_page_toc_auto_created';
    private const AUTO_MODULE_SOURCE_PAGE_META_KEY = '_lidingo_content_page_toc_source_page_id';
    private const MOBILE_BLOCK_CLASS = 'lidingo-content-page-toc-mobile-auto';
    private const RIGHT_SIDEBAR_ID = 'right-sidebar';
    private const TOC_BLOCK_NAME = 'acf/toc';
    private const TOC_POST_TYPE = 'mod-toc';
    private const TOC_MODULE_NAME = 'mod-toc';
    private const TOC_TITLE = 'Innehåll på sidan';
    private const NOTICE_TRANSIENT_PREFIX = 'lidingo_content_page_toc_bootstrap_notice_';

    private const FIELD_KEY_CUSTOM_BLOCK_TITLE = 'field_block_title';
    private const FIELD_KEY_SIDEBARS = 'field_6942499969780';
    private const FIELD_KEY_HEADING_LEVELS = 'field_694249ea69781';
    private const FIELD_KEY_HIDE_ON_MOBILE = 'field_67d6a94cb3c02';
    private const FIELD_KEY_HIDE_ON_DESKTOP = 'field_67d6c68ab3c03';
    private const FIELD_KEY_SETTINGS_VERSION = 'field_67d8328fb3c04';

    /** Prevent recursive bootstrap runs triggered by internal updates. */
    private array $activePostIds = [];

    public function addHooks(): void
    {
        add_action('wp_after_insert_post', [$this, 'bootstrap'], 20, 4);
        add_action('admin_notices', [$this, 'renderAdminNotice']);
    }

    /** Bootstrap mobile block and desktop module once when the template is first saved. */
    public function bootstrap(int $postId, WP_Post $post, bool $update, ?WP_Post $postBefore): void
    {
        unset($update, $postBefore);

        if (!$this->shouldBootstrap($postId, $post)) {
            return;
        }

        $this->activePostIds[$postId] = true;

        $mobileResult = $this->ensureMobileTocBlock($post);
        $desktopResult = $this->ensureDesktopTocModule($postId);
        $sidebarOptionsResult = $this->ensureRightSidebarHookBefore($postId);

        unset($this->activePostIds[$postId]);

        if ($mobileResult && $desktopResult && $sidebarOptionsResult) {
            update_post_meta($postId, self::BOOTSTRAP_COMPLETE_META_KEY, '1');
            return;
        }

        $this->queueAdminNotice(
            __('Kunde inte slutföra automatisk innehållsförteckning för mallen. Spara sidan igen eller kontrollera mobil- och desktop-TOC manuellt.', 'lidingo-customisation')
        );
    }

    private function shouldBootstrap(int $postId, WP_Post $post): bool
    {
        if ($post->post_type !== 'page') {
            return false;
        }

        if (isset($this->activePostIds[$postId])) {
            return false;
        }

        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return false;
        }

        if (get_post_meta($postId, self::BOOTSTRAP_COMPLETE_META_KEY, true) === '1') {
            return false;
        }

        if (get_page_template_slug($postId) !== ContentPageWithTocTemplate::TEMPLATE_SLUG) {
            return false;
        }

        return true;
    }

    private function ensureMobileTocBlock(WP_Post $post): bool
    {
        $blocks = parse_blocks((string) $post->post_content);

        if ($this->blocksContainToc($blocks)) {
            return true;
        }

        array_unshift($blocks, $this->buildMobileTocBlock());

        $updatedPostId = wp_update_post([
            'ID' => $post->ID,
            'post_content' => serialize_blocks($blocks),
        ], true);

        return !($updatedPostId instanceof \WP_Error);
    }

    private function ensureDesktopTocModule(int $postId): bool
    {
        $moduleSidebars = get_post_meta($postId, 'modularity-modules', true);
        $moduleSidebars = is_array($moduleSidebars) ? $moduleSidebars : [];

        if ($this->sidebarContainsTocModule($moduleSidebars[self::RIGHT_SIDEBAR_ID] ?? null)) {
            return true;
        }

        $moduleId = wp_insert_post([
            'post_type' => self::TOC_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => __('Innehåll på sidan', 'lidingo-customisation'),
        ], true);

        if (!is_int($moduleId) || $moduleId <= 0) {
            return false;
        }

        if (
            !$this->updateModuleField(self::FIELD_KEY_SIDEBARS, [], $moduleId)
            || !$this->updateModuleField(self::FIELD_KEY_HEADING_LEVELS, ['h2'], $moduleId)
            || !$this->updateModuleField(self::FIELD_KEY_HIDE_ON_MOBILE, '1', $moduleId)
            || !$this->updateModuleField(self::FIELD_KEY_HIDE_ON_DESKTOP, '0', $moduleId)
            || !$this->updateModuleField(self::FIELD_KEY_SETTINGS_VERSION, '2', $moduleId)
        ) {
            return false;
        }

        update_post_meta($moduleId, 'modularity-module-hide-title', '0');
        update_post_meta($moduleId, self::AUTO_MODULE_META_KEY, '1');
        update_post_meta($moduleId, self::AUTO_MODULE_SOURCE_PAGE_META_KEY, (string) $postId);
        update_post_meta($postId, self::DESKTOP_MODULE_ID_META_KEY, (string) $moduleId);

        $rightSidebar = $moduleSidebars[self::RIGHT_SIDEBAR_ID] ?? [];
        $rightSidebar = is_array($rightSidebar) ? $rightSidebar : [];
        $rightSidebar[$this->generateSidebarModuleKey()] = [
            'columnWidth' => '',
            'postid' => (string) $moduleId,
            'name' => self::TOC_MODULE_NAME,
            'hidden' => false,
        ];
        $moduleSidebars[self::RIGHT_SIDEBAR_ID] = $rightSidebar;

        return update_post_meta($postId, 'modularity-modules', $moduleSidebars) !== false;
    }

    private function ensureRightSidebarHookBefore(int $postId): bool
    {
        $sidebarOptions = get_post_meta($postId, 'modularity-sidebar-options', true);
        $sidebarOptions = is_array($sidebarOptions) ? $sidebarOptions : [];

        if (($sidebarOptions[self::RIGHT_SIDEBAR_ID]['hook'] ?? null) === 'before') {
            return true;
        }

        if (isset($sidebarOptions[self::RIGHT_SIDEBAR_ID]['hook']) && $sidebarOptions[self::RIGHT_SIDEBAR_ID]['hook'] !== '') {
            return true;
        }

        $sidebarOptions[self::RIGHT_SIDEBAR_ID]['hook'] = 'before';

        return update_post_meta($postId, 'modularity-sidebar-options', $sidebarOptions) !== false;
    }

    private function blocksContainToc(array $blocks): bool
    {
        foreach ($blocks as $block) {
            if (($block['blockName'] ?? '') === self::TOC_BLOCK_NAME) {
                return true;
            }

            if (!empty($block['innerBlocks']) && is_array($block['innerBlocks']) && $this->blocksContainToc($block['innerBlocks'])) {
                return true;
            }
        }

        return false;
    }

    private function buildMobileTocBlock(): array
    {
        return [
            'blockName' => self::TOC_BLOCK_NAME,
            'attrs' => [
                'name' => self::TOC_BLOCK_NAME,
                'mode' => 'preview',
                'className' => self::MOBILE_BLOCK_CLASS,
                'data' => [
                    'custom_block_title' => __('Innehåll på sidan', 'lidingo-customisation'),
                    '_custom_block_title' => self::FIELD_KEY_CUSTOM_BLOCK_TITLE,
                    'sidebars' => [],
                    '_sidebars' => self::FIELD_KEY_SIDEBARS,
                    'heading_levels' => ['h2'],
                    '_heading_levels' => self::FIELD_KEY_HEADING_LEVELS,
                    'hide_on_mobile' => '0',
                    '_hide_on_mobile' => self::FIELD_KEY_HIDE_ON_MOBILE,
                    'hide_on_desktop' => '1',
                    '_hide_on_desktop' => self::FIELD_KEY_HIDE_ON_DESKTOP,
                    'settings_version' => '2',
                    '_settings_version' => self::FIELD_KEY_SETTINGS_VERSION,
                ],
            ],
            'innerBlocks' => [],
            'innerHTML' => '',
            'innerContent' => [],
        ];
    }

    private function updateModuleField(string $fieldKey, mixed $value, int $postId): bool
    {
        $fieldName = $this->fieldNameFromKey($fieldKey);

        if ($fieldName === null) {
            return false;
        }

        update_post_meta($postId, $fieldName, $value);
        update_post_meta($postId, '_' . $fieldName, $fieldKey);

        return get_post_meta($postId, '_' . $fieldName, true) === $fieldKey;
    }

    private function fieldNameFromKey(string $fieldKey): ?string
    {
        return match ($fieldKey) {
            self::FIELD_KEY_SIDEBARS => 'sidebars',
            self::FIELD_KEY_HEADING_LEVELS => 'heading_levels',
            self::FIELD_KEY_HIDE_ON_MOBILE => 'hide_on_mobile',
            self::FIELD_KEY_HIDE_ON_DESKTOP => 'hide_on_desktop',
            self::FIELD_KEY_SETTINGS_VERSION => 'settings_version',
            default => null,
        };
    }

    private function sidebarContainsTocModule(mixed $sidebarModules): bool
    {
        if (!is_array($sidebarModules)) {
            return false;
        }

        foreach ($sidebarModules as $module) {
            if (!is_array($module)) {
                continue;
            }

            if (($module['name'] ?? '') === self::TOC_MODULE_NAME) {
                return true;
            }
        }

        return false;
    }

    private function generateSidebarModuleKey(): string
    {
        return str_replace('-', '', wp_generate_uuid4());
    }

    private function queueAdminNotice(string $message): void
    {
        $userId = get_current_user_id();

        if ($userId <= 0) {
            return;
        }

        set_transient(self::NOTICE_TRANSIENT_PREFIX . $userId, $message, MINUTE_IN_SECONDS * 5);
    }

    /** Render any queued bootstrap error notice for the current user. */
    public function renderAdminNotice(): void
    {
        if (!is_admin()) {
            return;
        }

        $userId = get_current_user_id();

        if ($userId <= 0) {
            return;
        }

        $transientKey = self::NOTICE_TRANSIENT_PREFIX . $userId;
        $message = get_transient($transientKey);

        if (!is_string($message) || $message === '') {
            return;
        }

        delete_transient($transientKey);

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html($message)
        );
    }
}
