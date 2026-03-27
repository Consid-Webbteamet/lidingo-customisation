<?php

declare(strict_types=1);

namespace LidingoCustomisation;

use LidingoCustomisation\AcfFields\ArchivePageFields;
use LidingoCustomisation\AcfFields\OngoingWorkDateFields;
use LidingoCustomisation\Archives\ArchiveLayout;
use LidingoCustomisation\Archives\OngoingWorkArchive;
use LidingoCustomisation\Components\HeroSearch\HeroSearchOverrides;
use LidingoCustomisation\Components\Posts\PostsDateOverrides;
use LidingoCustomisation\Components\Sections\SectionFullHeadingOverrides;
use LidingoCustomisation\Integrations\CustomerFeedback\CustomerFeedbackIntegration;
use LidingoCustomisation\Infrastructure\AssetRenderer;
use LidingoCustomisation\Infrastructure\AssetManifest;
use LidingoCustomisation\Infrastructure\CspHandler;
use LidingoCustomisation\Infrastructure\DevServer;
use LidingoCustomisation\Search\SearchPage;
use LidingoCustomisation\Templates\ArticlePageTemplate;
use LidingoCustomisation\Templates\ContentPageTemplate;
use LidingoCustomisation\Templates\EventPageTemplate;
use LidingoCustomisation\Templates\LandingPageTemplate;

class App
{
    private AssetManifest $assetManifest;
    private DevServer $devServer;
    private AssetRenderer $assetRenderer;
    private CspHandler $cspHandler;
    private ArchivePageFields $archivePageFields;
    private OngoingWorkDateFields $ongoingWorkDateFields;
    private HeroSearchOverrides $heroSearchOverrides;
    private PostsDateOverrides $postsDateOverrides;
    private SectionFullHeadingOverrides $sectionFullHeadingOverrides;
    private ArchiveLayout $archiveLayout;
    private OngoingWorkArchive $ongoingWorkArchive;
    private SearchPage $searchPage;
    private ArticlePageTemplate $articlePageTemplate;
    private EventPageTemplate $eventPageTemplate;
    private LandingPageTemplate $landingPageTemplate;
    private ContentPageTemplate $contentPageTemplate;
    private CustomerFeedbackIntegration $customerFeedbackIntegration;

    public function __construct()
    {
        $this->assetManifest = new AssetManifest(
            LIDINGO_CUSTOMISATION_PATH . 'dist/manifest.json',
            [
                'source/js/main.js',
                'source/sass/style.scss',
                'source/js/admin.js',
                'source/sass/admin.scss',
            ]
        );
        $this->devServer = new DevServer();
        $this->assetRenderer = new AssetRenderer($this->assetManifest, $this->devServer);
        $this->cspHandler = new CspHandler($this->devServer);
        $this->archivePageFields = new ArchivePageFields();
        $this->ongoingWorkDateFields = new OngoingWorkDateFields();
        $this->heroSearchOverrides = new HeroSearchOverrides();
        $this->postsDateOverrides = new PostsDateOverrides();
        $this->sectionFullHeadingOverrides = new SectionFullHeadingOverrides();
        $this->archiveLayout = new ArchiveLayout();
        $this->ongoingWorkArchive = new OngoingWorkArchive();
        $this->searchPage = new SearchPage();
        $this->articlePageTemplate = new ArticlePageTemplate();
        $this->eventPageTemplate = new EventPageTemplate();
        $this->landingPageTemplate = new LandingPageTemplate();
        $this->contentPageTemplate = new ContentPageTemplate();
        $this->customerFeedbackIntegration = new CustomerFeedbackIntegration();

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
        add_filter('Municipio/Template/viewData', [$this, 'adjustContentNoticePlacement'], 20, 1);
        add_filter('/Modularity/externalViewPath', [$this, 'addModularityExternalViewPaths']);
        $this->archivePageFields->addHooks();
        $this->ongoingWorkDateFields->addHooks();
        $this->heroSearchOverrides->addHooks();
        $this->postsDateOverrides->addHooks();
        $this->sectionFullHeadingOverrides->addHooks();
        $this->archiveLayout->addHooks();
        $this->ongoingWorkArchive->addHooks();
        $this->searchPage->addHooks();
        $this->articlePageTemplate->addHooks();
        $this->eventPageTemplate->addHooks();
        $this->landingPageTemplate->addHooks();
        $this->contentPageTemplate->addHooks();
        $this->customerFeedbackIntegration->addHooks();

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

    public function adjustContentNoticePlacement(array $viewData): array
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return $viewData;
        }

        if (get_page_template_slug($objectId) !== 'one-page.blade.php') {
            return $viewData;
        }

        $viewData['renderContentNoticesBeforeHero'] = true;

        return $viewData;
    }

    public function addModularityExternalViewPaths(array $paths): array
    {
        $paths['mod-posts'] = LIDINGO_CUSTOMISATION_PATH . 'source/modularity/mod-posts/views';

        return $paths;
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
