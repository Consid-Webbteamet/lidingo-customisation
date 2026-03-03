<?php

declare(strict_types=1);

namespace LidingoCustomisation;

use LidingoCustomisation\Infrastructure\AssetManifest;

class App
{
    private AssetManifest $assetManifest;
    private bool $hasPrintedViteClient = false;

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

        if ($this->shouldUseDevServer()) {
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

        if ($this->shouldUseDevServer()) {
            $this->printDevModuleScript('source/js/main.js', 'lidingo-customisation-main-js-dev');
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

        if ($this->shouldUseDevServer()) {
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

        if ($this->shouldUseDevServer()) {
            $this->printDevModuleScript('source/js/admin.js', 'lidingo-customisation-admin-js-dev');
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
        if (!is_admin() || !current_user_can('manage_options') || $this->shouldUseDevServer()) {
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

    private function shouldUseDevServer(): bool
    {
        $enabledByDefault = defined('WP_ENV') && WP_ENV === 'development';

        $enabled = (bool) apply_filters(
            'lidingo_customisation/use_vite_dev_server',
            $enabledByDefault
        );

        if (!$enabled) {
            return false;
        }

        return $this->isDevServerReachable();
    }

    private function isDevServerReachable(): bool
    {
        static $reachable = null;

        if ($reachable !== null) {
            return $reachable;
        }

        $response = wp_remote_get(
            $this->getDevServerOrigin() . '/@vite/client',
            [
                'timeout' => 0.6,
                'sslverify' => false,
            ]
        );

        if (is_wp_error($response)) {
            $reachable = false;
            return false;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $reachable = $statusCode >= 200 && $statusCode < 500;

        return $reachable;
    }

    private function printDevModuleScript(string $entryPath, string $id): void
    {
        $this->printViteClientScript();

        printf(
            '<script type="module" id="%s" src="%s"></script>' . "\n",
            esc_attr($id),
            esc_url($this->getDevServerOrigin() . '/' . ltrim($entryPath, '/'))
        );
    }

    private function printViteClientScript(): void
    {
        if ($this->hasPrintedViteClient) {
            return;
        }

        $this->hasPrintedViteClient = true;

        printf(
            '<script type="module" id="lidingo-customisation-vite-client" src="%s"></script>' . "\n",
            esc_url($this->getDevServerOrigin() . '/@vite/client')
        );
    }

    private function getDevServerOrigin(): string
    {
        $origin = (string) apply_filters(
            'lidingo_customisation/dev_server_origin',
            'http://localhost:5173'
        );

        return rtrim($origin, '/');
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
