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
        add_filter('WpSecurity/Csp', [$this, 'addDevServerCspDomains'], 10, 1);
        add_filter('Website/HTML/output', [$this, 'stripDevBlockingCspDirectives'], 20, 0);

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

    public function addDevServerCspDomains(array $domains): array
    {
        if (!$this->shouldUseDevServer()) {
            return $domains;
        }

        $devServerHost = $this->getDevServerHostWithPort();

        if ($devServerHost === null) {
            return $domains;
        }

        $requiredByDirective = [
            'script-src' => [$devServerHost],
            'style-src' => [$devServerHost],
            'connect-src' => [$devServerHost, $this->getDevServerWsOrigin()],
        ];

        foreach ($requiredByDirective as $directive => $values) {
            if (!isset($domains[$directive]) || !is_array($domains[$directive])) {
                $domains[$directive] = [];
            }

            $domains[$directive] = $this->mergeCspDirectiveValues($domains[$directive], $values);
        }

        return $domains;
    }

    public function stripDevBlockingCspDirectives(): void
    {
        if (!$this->shouldUseDevServer()) {
            return;
        }

        $headerValue = $this->getContentSecurityPolicyHeaderValue();

        if ($headerValue === null || $headerValue === '') {
            return;
        }

        $directives = array_filter(array_map('trim', explode(';', $headerValue)));

        $filteredDirectives = array_values(array_filter(
            $directives,
            static function (string $directive): bool {
                $normalized = strtolower($directive);

                return $normalized !== 'upgrade-insecure-requests'
                    && $normalized !== 'block-all-mixed-content';
            }
        ));

        header_remove('Content-Security-Policy');

        if (!empty($filteredDirectives)) {
            header('Content-Security-Policy: ' . implode('; ', $filteredDirectives));
        }
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

    private function getContentSecurityPolicyHeaderValue(): ?string
    {
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Security-Policy:') !== 0) {
                continue;
            }

            return trim(substr($header, strlen('Content-Security-Policy:')));
        }

        return null;
    }

    private function getDevServerHostWithPort(): ?string
    {
        $parsedOrigin = parse_url($this->getDevServerOrigin());

        if (!is_array($parsedOrigin) || empty($parsedOrigin['host'])) {
            return null;
        }

        $host = strtolower((string) $parsedOrigin['host']);

        if (isset($parsedOrigin['port'])) {
            $host .= ':' . (int) $parsedOrigin['port'];
        }

        return $host;
    }

    private function getDevServerWsOrigin(): ?string
    {
        $parsedOrigin = parse_url($this->getDevServerOrigin());

        if (!is_array($parsedOrigin) || empty($parsedOrigin['host'])) {
            return null;
        }

        $httpScheme = strtolower((string) ($parsedOrigin['scheme'] ?? 'http'));
        $wsScheme = $httpScheme === 'https' ? 'wss' : 'ws';

        $host = strtolower((string) $parsedOrigin['host']);

        if (isset($parsedOrigin['port'])) {
            $host .= ':' . (int) $parsedOrigin['port'];
        }

        return $wsScheme . '://' . $host;
    }

    private function mergeCspDirectiveValues(array $currentValues, array $valuesToAppend): array
    {
        $merged = [];

        foreach ($currentValues as $value) {
            if (!is_string($value)) {
                continue;
            }

            $trimmed = trim($value);

            if ($trimmed === '' || $trimmed === "'none'") {
                continue;
            }

            $merged[] = $trimmed;
        }

        foreach ($valuesToAppend as $value) {
            if (!is_string($value)) {
                continue;
            }

            $trimmed = trim($value);

            if ($trimmed === '') {
                continue;
            }

            $merged[] = $trimmed;
        }

        return array_values(array_unique($merged));
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
