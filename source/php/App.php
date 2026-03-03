<?php

declare(strict_types=1);

namespace LidingoCustomisation;

use LidingoCustomisation\Infrastructure\AssetManifest;

class App
{
    private AssetManifest $assetManifest;

    public function __construct()
    {
        $this->assetManifest = new AssetManifest(LIDINGO_CUSTOMISATION_PATH . 'dist/.vite/manifest.json');

        $this->addHooks();
    }

    private function addHooks(): void
    {
        add_action('wp_head', [$this, 'printFrontendStylesheet'], 1001);
        add_action('wp_footer', [$this, 'printFrontendScript'], 1001);
        add_action('admin_head', [$this, 'printAdminStylesheet'], 1001);
        add_action('admin_footer', [$this, 'printAdminScript'], 1001);

        if (!$this->assetManifest->isLoaded()) {
            add_action('admin_notices', [$this, 'renderMissingManifestNotice']);
        }
    }

    public function printFrontendStylesheet(): void
    {
        if (is_admin() || !$this->shouldLoadFrontend()) {
            return;
        }

        $href = $this->assetManifest->getAssetUrl('source/sass/style.scss', LIDINGO_CUSTOMISATION_URL . 'dist/');

        if ($href === null) {
            return;
        }

        printf(
            '<link rel="stylesheet" id="lidingo-customisation-style" href="%s" media="all" />' . "\n",
            esc_url($href)
        );
    }

    public function printFrontendScript(): void
    {
        if (is_admin() || !$this->shouldLoadFrontend()) {
            return;
        }

        $src = $this->assetManifest->getAssetUrl('source/js/main.js', LIDINGO_CUSTOMISATION_URL . 'dist/');

        if ($src === null) {
            return;
        }

        printf(
            '<script id="lidingo-customisation-main-js" src="%s" defer></script>' . "\n",
            esc_url($src)
        );
    }

    public function printAdminStylesheet(): void
    {
        if (!is_admin() || !$this->shouldLoadAdmin()) {
            return;
        }

        $href = $this->assetManifest->getAssetUrl('source/sass/admin.scss', LIDINGO_CUSTOMISATION_URL . 'dist/');

        if ($href === null) {
            return;
        }

        printf(
            '<link rel="stylesheet" id="lidingo-customisation-admin-style" href="%s" media="all" />' . "\n",
            esc_url($href)
        );
    }

    public function printAdminScript(): void
    {
        if (!is_admin() || !$this->shouldLoadAdmin()) {
            return;
        }

        $src = $this->assetManifest->getAssetUrl('source/js/admin.js', LIDINGO_CUSTOMISATION_URL . 'dist/');

        if ($src === null) {
            return;
        }

        printf(
            '<script id="lidingo-customisation-admin-js" src="%s" defer></script>' . "\n",
            esc_url($src)
        );
    }

    public function renderMissingManifestNotice(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $message = $this->assetManifest->getErrorMessage();

        if ($message === null) {
            $message = __('Unable to load Lidingo Customisation assets.', 'lidingo-customisation');
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html($message)
        );
    }

    private function shouldLoadFrontend(): bool
    {
        return (bool) apply_filters('lidingo_customisation/should_load_frontend', true);
    }

    private function shouldLoadAdmin(): bool
    {
        return (bool) apply_filters('lidingo_customisation/should_load_admin', false);
    }
}
