<?php

declare(strict_types=1);

namespace LidingoCustomisation;

use LidingoCustomisation\AcfFields\ArchivePageFields;
use LidingoCustomisation\AcfFields\HeroFields;
use LidingoCustomisation\AcfFields\ModularityTocFields;
use LidingoCustomisation\AcfFields\NewsDisplayFields;
use LidingoCustomisation\AcfFields\OngoingWorkDateFields;
use LidingoCustomisation\AcfFields\PageFilterFields;
use LidingoCustomisation\AcfFields\ServiceInfoArchivePageFields;
use LidingoCustomisation\AcfFields\ServiceInfoCategoryIconFields;
use LidingoCustomisation\AcfFields\ServiceInfoSingleSidebarFields;
use LidingoCustomisation\Archives\ArchiveLayout;
use LidingoCustomisation\Archives\OngoingWorkArchive;
use LidingoCustomisation\Archives\ServiceInfoArchive;
use LidingoCustomisation\Components\HeroSearch\HeroSearchOverrides;
use LidingoCustomisation\Components\Posts\PostsDateOverrides;
use LidingoCustomisation\Components\Sections\SectionFullHeadingOverrides;
use LidingoCustomisation\Integrations\CustomerFeedback\CustomerFeedbackIntegration;
use LidingoCustomisation\Integrations\RekAi\RekAiIntegration;
use LidingoCustomisation\Integrations\ServiceInfo\ServiceInfoIntegration;
use LidingoCustomisation\Infrastructure\AssetRenderer;
use LidingoCustomisation\Infrastructure\AssetManifest;
use LidingoCustomisation\Infrastructure\CspHandler;
use LidingoCustomisation\Infrastructure\DevServer;
use LidingoCustomisation\Search\SearchPage;
use LidingoCustomisation\Templates\ArticlePageTemplate;
use LidingoCustomisation\Templates\ContentPageTemplate;
use LidingoCustomisation\Templates\ContentPageWithTocBootstrap;
use LidingoCustomisation\Templates\ContentPageWithTocTemplate;
use LidingoCustomisation\Templates\EventPageTemplate;
use LidingoCustomisation\Templates\JobListingTemplate;
use LidingoCustomisation\Templates\JobPostingTemplate;
use LidingoCustomisation\Templates\LandingPageTemplate;
use LidingoCustomisation\Templates\PageFilterTemplate;

class App
{
    private AssetManifest $assetManifest;
    private DevServer $devServer;
    private AssetRenderer $assetRenderer;
    private CspHandler $cspHandler;
    private ArchivePageFields $archivePageFields;
    private HeroFields $heroFields;
    private ModularityTocFields $modularityTocFields;
    private NewsDisplayFields $newsDisplayFields;
    private OngoingWorkDateFields $ongoingWorkDateFields;
    private PageFilterFields $pageFilterFields;
    private ServiceInfoArchivePageFields $serviceInfoArchivePageFields;
    private ServiceInfoCategoryIconFields $serviceInfoCategoryIconFields;
    private ServiceInfoSingleSidebarFields $serviceInfoSingleSidebarFields;
    private HeroSearchOverrides $heroSearchOverrides;
    private PostsDateOverrides $postsDateOverrides;
    private SectionFullHeadingOverrides $sectionFullHeadingOverrides;
    private ArchiveLayout $archiveLayout;
    private OngoingWorkArchive $ongoingWorkArchive;
    private ServiceInfoArchive $serviceInfoArchive;
    private SearchPage $searchPage;
    private ArticlePageTemplate $articlePageTemplate;
    private EventPageTemplate $eventPageTemplate;
    private JobListingTemplate $jobListingTemplate;
    private JobPostingTemplate $jobPostingTemplate;
    private LandingPageTemplate $landingPageTemplate;
    private PageFilterTemplate $pageFilterTemplate;
    private ContentPageTemplate $contentPageTemplate;
    private ContentPageWithTocTemplate $contentPageWithTocTemplate;
    private ContentPageWithTocBootstrap $contentPageWithTocBootstrap;
    private CustomerFeedbackIntegration $customerFeedbackIntegration;
    private RekAiIntegration $rekAiIntegration;
    private ServiceInfoIntegration $serviceInfoIntegration;

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
        $this->heroFields = new HeroFields();
        $this->modularityTocFields = new ModularityTocFields();
        $this->newsDisplayFields = new NewsDisplayFields();
        $this->ongoingWorkDateFields = new OngoingWorkDateFields();
        $this->pageFilterFields = new PageFilterFields();
        $this->serviceInfoArchivePageFields = new ServiceInfoArchivePageFields();
        $this->serviceInfoCategoryIconFields = new ServiceInfoCategoryIconFields();
        $this->serviceInfoSingleSidebarFields = new ServiceInfoSingleSidebarFields();
        $this->heroSearchOverrides = new HeroSearchOverrides();
        $this->postsDateOverrides = new PostsDateOverrides();
        $this->sectionFullHeadingOverrides = new SectionFullHeadingOverrides();
        $this->archiveLayout = new ArchiveLayout();
        $this->ongoingWorkArchive = new OngoingWorkArchive();
        $this->serviceInfoArchive = new ServiceInfoArchive();
        $this->searchPage = new SearchPage();
        $this->articlePageTemplate = new ArticlePageTemplate();
        $this->eventPageTemplate = new EventPageTemplate();
        $this->jobListingTemplate = new JobListingTemplate();
        $this->jobPostingTemplate = new JobPostingTemplate();
        $this->landingPageTemplate = new LandingPageTemplate();
        $this->pageFilterTemplate = new PageFilterTemplate();
        $this->contentPageTemplate = new ContentPageTemplate();
        $this->contentPageWithTocTemplate = new ContentPageWithTocTemplate();
        $this->contentPageWithTocBootstrap = new ContentPageWithTocBootstrap();
        $this->customerFeedbackIntegration = new CustomerFeedbackIntegration();
        $this->rekAiIntegration = new RekAiIntegration();
        $this->serviceInfoIntegration = new ServiceInfoIntegration();

        $this->addHooks();
    }

    /** Register plugin hooks and subcomponents. */
    private function addHooks(): void
    {
        add_action('wp_head', [$this, 'printFrontendStylesheet'], 1001);
        add_action('wp_footer', [$this, 'printFrontendScript'], 1001);
        add_action('admin_head', [$this, 'printAdminStylesheet'], 1001);
        add_action('admin_footer', [$this, 'printAdminScript'], 1001);
        add_action('login_enqueue_scripts', [$this, 'printLoginStyles'], 1001);
        add_filter('body_class', [$this, 'addFrontendBodyClasses'], 20, 1);
        add_filter('theme_page_templates', [$this, 'customizeEditorPageTemplates'], 20, 4);
        add_filter('WpSecurity/Csp', [$this, 'addDevServerCspDomains'], 10, 1);
        add_filter('Website/HTML/output', [$this, 'stripDevBlockingCspDirectives'], 20, 0);
        add_filter('Municipio/Template/viewData', [$this, 'adjustContentNoticePlacement'], 20, 1);
        add_filter('/Modularity/externalViewPath', [$this, 'addModularityExternalViewPaths']);
        $this->archivePageFields->addHooks();
        $this->heroFields->addHooks();
        $this->modularityTocFields->addHooks();
        $this->newsDisplayFields->addHooks();
        $this->ongoingWorkDateFields->addHooks();
        $this->pageFilterFields->addHooks();
        $this->serviceInfoArchivePageFields->addHooks();
        $this->serviceInfoCategoryIconFields->addHooks();
        $this->serviceInfoSingleSidebarFields->addHooks();
        $this->heroSearchOverrides->addHooks();
        $this->postsDateOverrides->addHooks();
        $this->sectionFullHeadingOverrides->addHooks();
        $this->archiveLayout->addHooks();
        $this->ongoingWorkArchive->addHooks();
        $this->serviceInfoArchive->addHooks();
        $this->searchPage->addHooks();
        $this->articlePageTemplate->addHooks();
        $this->eventPageTemplate->addHooks();
        $this->jobListingTemplate->addHooks();
        $this->jobPostingTemplate->addHooks();
        $this->landingPageTemplate->addHooks();
        $this->pageFilterTemplate->addHooks();
        $this->contentPageTemplate->addHooks();
        $this->contentPageWithTocTemplate->addHooks();
        $this->contentPageWithTocBootstrap->addHooks();
        $this->customerFeedbackIntegration->addHooks();
        $this->rekAiIntegration->addHooks();
        $this->serviceInfoIntegration->addHooks();

        if (!$this->assetManifest->isLoaded()) {
            add_action('admin_notices', [$this, 'renderMissingManifestNotice']);
        }
    }

    /** Print the frontend stylesheet. */
    public function printFrontendStylesheet(): void
    {
        if (is_admin() || !$this->shouldLoadFrontend()) {
            return;
        }

        $this->assetRenderer->printFrontendStylesheet();
        $this->printFullPageBackgroundStyles();
    }

    /** Print the frontend script. */
    public function printFrontendScript(): void
    {
        if (is_admin() || !$this->shouldLoadFrontend()) {
            return;
        }

        $this->assetRenderer->printFrontendScript();
    }

    /** Print the admin stylesheet. */
    public function printAdminStylesheet(): void
    {
        if (!is_admin() || !$this->shouldLoadAdminAssets()) {
            return;
        }

        $this->assetRenderer->printAdminStylesheet();
    }

    /** Print the admin script. */
    public function printAdminScript(): void
    {
        if (!is_admin() || !$this->shouldLoadAdminAssets()) {
            return;
        }

        $this->assetRenderer->printAdminScript();
    }

    /** Apply login page background styling. */
    public function printLoginStyles(): void
    {
        echo '<style>body.login{background-color:#002B49;background-image:none;}</style>';
    }

    /** Add body classes used by frontend page-level styling. */
    public function addFrontendBodyClasses(array $classes): array
    {
        if (!$this->shouldUseFullPageBackground()) {
            return $classes;
        }

        $classes[] = 'lidingo-full-page-background';

        return array_values(array_unique($classes));
    }

    /** Print inline CSS variables for the full-page background image. */
    private function printFullPageBackgroundStyles(): void
    {
        if (!$this->shouldUseFullPageBackground()) {
            return;
        }

        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return;
        }

        $backgroundImage = get_the_post_thumbnail_url($objectId, 'full');

        if (!is_string($backgroundImage) || $backgroundImage === '') {
            return;
        }

        printf(
            '<style id="lidingo-customisation-full-page-background">body.lidingo-full-page-background{--lidingo-full-page-background-image:url("%s");}</style>',
            esc_url_raw($backgroundImage)
        );
    }

    /** Show a warning when the manifest is missing. */
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

    /** Add dev server domains to the CSP allowlist. */
    public function addDevServerCspDomains(array $domains): array
    {
        return $this->cspHandler->addDevServerCspDomains($domains);
    }

    /** Remove blocking CSP directives in dev. */
    public function stripDevBlockingCspDirectives(): void
    {
        $this->cspHandler->stripDevBlockingCspDirectives();
    }

    /** Move content notices above the hero on the one-page template. */
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

    /** Hide editor templates that should not be available for pages. */
    public function customizeEditorPageTemplates(array $pageTemplates, \WP_Theme $theme, ?\WP_Post $post, string $postType): array
    {
        if ($postType !== 'page') {
            return $pageTemplates;
        }

        unset($pageTemplates['page-centered.blade.php']);

        if (isset($pageTemplates['one-page.blade.php'])) {
            $pageTemplates['one-page.blade.php'] = __('Startsida', 'lidingo-customisation');
        }

        return $pageTemplates;
    }

    /** Point Modularity to the package-local view overrides. */
    public function addModularityExternalViewPaths(array $paths): array
    {
        $paths['mod-posts'] = LIDINGO_CUSTOMISATION_PATH . 'source/modularity/mod-posts/views';

        return $paths;
    }

    private function shouldLoadFrontend(): bool
    {
        return (bool) apply_filters('lidingo_customisation/should_load_frontend', true);
    }

    /** Determine whether the current page should use a full-page background image. */
    private function shouldUseFullPageBackground(): bool
    {
        $objectId = get_queried_object_id();

        if (!is_int($objectId) || $objectId <= 0) {
            return false;
        }

        $templateSlug = get_page_template_slug($objectId);
        $allowedTemplates = [
            'one-page.blade.php',
            LandingPageTemplate::TEMPLATE_SLUG,
        ];

        if (!is_front_page() && !in_array($templateSlug, $allowedTemplates, true)) {
            return false;
        }

        if (!$this->hasStepQueryParam()) {
            return false;
        }

        return has_post_thumbnail($objectId);
    }

    /** Detect Event plugin step URLs like ?45-step=1 in a domain-agnostic way. */
    private function hasStepQueryParam(): bool
    {
        foreach (array_keys($_GET) as $queryKey) {
            if (!is_string($queryKey)) {
                continue;
            }

            if (preg_match('/^\d+-step$/', $queryKey) === 1) {
                return true;
            }
        }

        return false;
    }

    private function shouldLoadAdmin(): bool
    {
        return (bool) apply_filters('lidingo_customisation/should_load_admin', false);
    }

    /** Load admin assets for the dashboard or the block editor screen. */
    private function shouldLoadAdminAssets(): bool
    {
        return $this->shouldLoadAdmin() || $this->isBlockEditorScreen();
    }

    private function isBlockEditorScreen(): bool
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        return $screen !== null && method_exists($screen, 'is_block_editor') && $screen->is_block_editor();
    }
}
