<?php

declare(strict_types=1);

namespace LidingoCustomisation;

use LidingoCustomisation\Components\HeroSearch\HeroSearchOverrides;
use LidingoCustomisation\Components\Posts\PostsDateOverrides;
use LidingoCustomisation\EventAdmin\LegacyApiAdapter;
use LidingoCustomisation\Infrastructure\AssetRenderer;
use LidingoCustomisation\Infrastructure\AssetManifest;
use LidingoCustomisation\Infrastructure\CspHandler;
use LidingoCustomisation\Infrastructure\DevServer;
use LidingoCustomisation\Templates\EventTemplateOverrides;
use LidingoCustomisation\Templates\LandingPageTemplate;

class App
{
    private AssetManifest $assetManifest;
    private DevServer $devServer;
    private AssetRenderer $assetRenderer;
    private CspHandler $cspHandler;
    private HeroSearchOverrides $heroSearchOverrides;
    private PostsDateOverrides $postsDateOverrides;
    private LandingPageTemplate $landingPageTemplate;
    private EventTemplateOverrides $eventTemplateOverrides;
    private LegacyApiAdapter $legacyApiAdapter;

    public function __construct()
    {
        $this->assetManifest = new AssetManifest(LIDINGO_CUSTOMISATION_PATH . 'dist/.vite/manifest.json');
        $this->devServer = new DevServer();
        $this->assetRenderer = new AssetRenderer($this->assetManifest, $this->devServer);
        $this->cspHandler = new CspHandler($this->devServer);
        $this->heroSearchOverrides = new HeroSearchOverrides();
        $this->postsDateOverrides = new PostsDateOverrides();
        $this->landingPageTemplate = new LandingPageTemplate();
        $this->eventTemplateOverrides = new EventTemplateOverrides();
        $this->legacyApiAdapter = new LegacyApiAdapter();

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
        $this->heroSearchOverrides->addHooks();
        $this->postsDateOverrides->addHooks();
        $this->landingPageTemplate->addHooks();
        $this->eventTemplateOverrides->addHooks();
        $this->legacyApiAdapter->addHooks();

        if (!$this->assetManifest->isLoaded()) {
            add_action('admin_notices', [$this, 'renderMissingManifestNotice']);
        }
    }

    public function printFrontendStylesheet(): void
    {
        if (is_admin() || !$this->shouldLoadFrontend()) {
            return;
        }

        $this->assetRenderer->printFrontendStylesheet();
    }

    public function printFrontendScript(): void
    {
        if (is_admin() || !$this->shouldLoadFrontend()) {
            return;
        }

        $this->assetRenderer->printFrontendScript();
    }

    public function printAdminStylesheet(): void
    {
        if (!is_admin() || !$this->shouldLoadAdmin()) {
            return;
        }

        $this->assetRenderer->printAdminStylesheet();
    }

    public function printAdminScript(): void
    {
        if (!is_admin() || !$this->shouldLoadAdmin()) {
            return;
        }

        $this->assetRenderer->printAdminScript();
    }

    public function renderMissingManifestNotice(): void
    {
        if (!is_admin() || !current_user_can('manage_options') || $this->devServer->shouldUseDevServer()) {
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
        return $this->cspHandler->addDevServerCspDomains($domains);
    }

    public function stripDevBlockingCspDirectives(): void
    {
        $this->cspHandler->stripDevBlockingCspDirectives();
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
