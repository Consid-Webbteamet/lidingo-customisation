<?php

declare(strict_types=1);

namespace LidingoCustomisation;

use LidingoCustomisation\AcfFields\ArchivePageFields;
use LidingoCustomisation\AcfFields\HeroFields;
use LidingoCustomisation\AcfFields\ModularityTocFields;
use LidingoCustomisation\AcfFields\NewsDisplayFields;
use LidingoCustomisation\AcfFields\OngoingWorkDateFields;
use LidingoCustomisation\AcfFields\ServiceInfoArchivePageFields;
use LidingoCustomisation\AcfFields\ServiceInfoCategoryIconFields;
use LidingoCustomisation\AcfFields\ServiceInfoSingleSidebarFields;
use LidingoCustomisation\Archives\ArchiveLayout;
use LidingoCustomisation\Archives\OngoingWorkArchive;
use LidingoCustomisation\Archives\ServiceInfoArchive;
use LidingoCustomisation\Components\HeaderSearch\HeaderSearchOverrides;
use LidingoCustomisation\Components\HeroSearch\HeroSearchOverrides;
use LidingoCustomisation\Components\Posts\FeaturedNewsPosts;
use LidingoCustomisation\Components\Posts\PostsDateOverrides;
use LidingoCustomisation\Components\Posts\StickyPostsOverrides;
use LidingoCustomisation\Components\Sections\SectionFullHeadingOverrides;
use LidingoCustomisation\Integrations\CustomerFeedback\CustomerFeedbackIntegration;
use LidingoCustomisation\Integrations\RekAi\RekAiIntegration;
use LidingoCustomisation\Integrations\ServiceInfo\ServiceInfoIntegration;
use LidingoCustomisation\Infrastructure\AssetRenderer;
use LidingoCustomisation\Infrastructure\AssetManifest;
use LidingoCustomisation\Infrastructure\CspHandler;
use LidingoCustomisation\Infrastructure\DevServer;
use LidingoCustomisation\Navigation\DrawerMenuAppend;
use LidingoCustomisation\Presentation\PagePresentation;
use LidingoCustomisation\Search\SearchPage;
use LidingoCustomisation\Templates\ArticlePageTemplate;
use LidingoCustomisation\Templates\ContentPageTemplate;
use LidingoCustomisation\Templates\ContentPageWithTocBootstrap;
use LidingoCustomisation\Templates\ContentPageWithTocTemplate;
use LidingoCustomisation\Templates\EventPageTemplate;
use LidingoCustomisation\Templates\JobListingTemplate;
use LidingoCustomisation\Templates\JobPostingTemplate;
use LidingoCustomisation\Templates\LandingPageTemplate;
use LidingoCustomisation\Templates\PageTemplatePostTypes;
use LidingoCustomisation\Typography\FontDisplay;

class App
{
    private const GLOBAL_NOTICES_TYPE_FIELD_KEY = 'field_6798fa82cc9ba';
    private const GLOBAL_NOTICES_ALLOWED_TYPES = ['warning', 'danger'];
    private const GLOBAL_NOTICES_DEFAULT_TYPE = 'warning';

    private AssetManifest $assetManifest;
    private DevServer $devServer;
    private AssetRenderer $assetRenderer;
    private CspHandler $cspHandler;
    private PagePresentation $pagePresentation;
    private ArchivePageFields $archivePageFields;
    private HeroFields $heroFields;
    private ModularityTocFields $modularityTocFields;
    private NewsDisplayFields $newsDisplayFields;
    private OngoingWorkDateFields $ongoingWorkDateFields;
    private ServiceInfoArchivePageFields $serviceInfoArchivePageFields;
    private ServiceInfoCategoryIconFields $serviceInfoCategoryIconFields;
    private ServiceInfoSingleSidebarFields $serviceInfoSingleSidebarFields;
    private HeaderSearchOverrides $headerSearchOverrides;
    private HeroSearchOverrides $heroSearchOverrides;
    private FeaturedNewsPosts $featuredNewsPosts;
    private PostsDateOverrides $postsDateOverrides;
    private StickyPostsOverrides $stickyPostsOverrides;
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
    private ContentPageTemplate $contentPageTemplate;
    private ContentPageWithTocTemplate $contentPageWithTocTemplate;
    private ContentPageWithTocBootstrap $contentPageWithTocBootstrap;
    private CustomerFeedbackIntegration $customerFeedbackIntegration;
    private RekAiIntegration $rekAiIntegration;
    private ServiceInfoIntegration $serviceInfoIntegration;
    private DrawerMenuAppend $drawerMenuAppend;
    private FontDisplay $fontDisplay;

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
        $this->pagePresentation = new PagePresentation();
        $this->archivePageFields = new ArchivePageFields();
        $this->heroFields = new HeroFields();
        $this->modularityTocFields = new ModularityTocFields();
        $this->newsDisplayFields = new NewsDisplayFields();
        $this->ongoingWorkDateFields = new OngoingWorkDateFields();
        $this->serviceInfoArchivePageFields = new ServiceInfoArchivePageFields();
        $this->serviceInfoCategoryIconFields = new ServiceInfoCategoryIconFields();
        $this->serviceInfoSingleSidebarFields = new ServiceInfoSingleSidebarFields();
        $this->headerSearchOverrides = new HeaderSearchOverrides();
        $this->heroSearchOverrides = new HeroSearchOverrides();
        $this->featuredNewsPosts = new FeaturedNewsPosts();
        $this->postsDateOverrides = new PostsDateOverrides();
        $this->stickyPostsOverrides = new StickyPostsOverrides();
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
        $this->contentPageTemplate = new ContentPageTemplate();
        $this->contentPageWithTocTemplate = new ContentPageWithTocTemplate();
        $this->contentPageWithTocBootstrap = new ContentPageWithTocBootstrap();
        $this->customerFeedbackIntegration = new CustomerFeedbackIntegration();
        $this->rekAiIntegration = new RekAiIntegration();
        $this->serviceInfoIntegration = new ServiceInfoIntegration();
        $this->drawerMenuAppend = new DrawerMenuAppend();
        $this->fontDisplay = new FontDisplay();

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
        add_action('init', [$this, 'enablePageTemplateSupports'], 20);
        add_filter('acf/load_field/key=' . self::GLOBAL_NOTICES_TYPE_FIELD_KEY, [$this, 'filterGlobalNoticeTypeField']);
        add_filter('acf/load_value/key=' . self::GLOBAL_NOTICES_TYPE_FIELD_KEY, [$this, 'normalizeGlobalNoticeType'], 20, 3);
        add_filter('acf/update_value/key=' . self::GLOBAL_NOTICES_TYPE_FIELD_KEY, [$this, 'normalizeGlobalNoticeType'], 20, 3);
        add_filter('WpSecurity/Csp', [$this, 'addDevServerCspDomains'], 10, 1);
        add_filter('Website/HTML/output', [$this, 'stripDevBlockingCspDirectives'], 20, 0);
        $this->pagePresentation->addHooks();
        $this->archivePageFields->addHooks();
        $this->heroFields->addHooks();
        $this->modularityTocFields->addHooks();
        $this->newsDisplayFields->addHooks();
        $this->ongoingWorkDateFields->addHooks();
        $this->serviceInfoArchivePageFields->addHooks();
        $this->serviceInfoCategoryIconFields->addHooks();
        $this->serviceInfoSingleSidebarFields->addHooks();
        $this->headerSearchOverrides->addHooks();
        $this->heroSearchOverrides->addHooks();
        $this->featuredNewsPosts->addHooks();
        $this->postsDateOverrides->addHooks();
        $this->stickyPostsOverrides->addHooks();
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
        $this->contentPageTemplate->addHooks();
        $this->contentPageWithTocTemplate->addHooks();
        $this->contentPageWithTocBootstrap->addHooks();
        $this->customerFeedbackIntegration->addHooks();
        $this->rekAiIntegration->addHooks();
        $this->serviceInfoIntegration->addHooks();
        $this->drawerMenuAppend->addHooks();
        $this->fontDisplay->addHooks();

        if (!$this->assetManifest->isLoaded()) {
            add_action('admin_notices', [$this, 'renderMissingManifestNotice']);
        }
    }

    /** Print frontend assets and inline background styles when frontend loading is enabled. */
    public function printFrontendStylesheet(): void
    {
        if (is_admin() || !$this->shouldLoadFrontend()) {
            return;
        }

        $this->assetRenderer->printFrontendStylesheet();
        $this->pagePresentation->printFullPageBackgroundStyles();
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

    /** Enable page template UI and parent/order controls on supported post types. */
    public function enablePageTemplateSupports(): void
    {
        foreach (PageTemplatePostTypes::get() as $postType) {
            add_post_type_support($postType, 'page-attributes');
        }
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

    /** Limit global notice levels to warning and danger. */
    public function filterGlobalNoticeTypeField($field)
    {
        if (!is_array($field)) {
            return $field;
        }

        $choices = is_array($field['choices'] ?? null) ? $field['choices'] : [];
        $field['choices'] = array_intersect_key($choices, array_flip(self::GLOBAL_NOTICES_ALLOWED_TYPES));
        $field['default_value'] = self::GLOBAL_NOTICES_DEFAULT_TYPE;

        return $field;
    }

    /** Normalize legacy or unsupported global notice levels. */
    public function normalizeGlobalNoticeType($value)
    {
        if (!is_string($value)) {
            return self::GLOBAL_NOTICES_DEFAULT_TYPE;
        }

        $value = sanitize_key($value);

        return in_array($value, self::GLOBAL_NOTICES_ALLOWED_TYPES, true)
            ? $value
            : self::GLOBAL_NOTICES_DEFAULT_TYPE;
    }

    /** Read the frontend asset toggle from the project filter. */
    private function shouldLoadFrontend(): bool
    {
        return (bool) apply_filters('lidingo_customisation/should_load_frontend', true);
    }

    /** Read the explicit admin asset toggle from the project filter. */
    private function shouldLoadAdmin(): bool
    {
        return (bool) apply_filters('lidingo_customisation/should_load_admin', false);
    }

    /** Load admin assets for the dashboard or the block editor screen. */
    private function shouldLoadAdminAssets(): bool
    {
        return $this->shouldLoadAdmin() || $this->isBlockEditorScreen();
    }

    /** Detect the block editor screen without assuming the screen API is always loaded. */
    private function isBlockEditorScreen(): bool
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        return $screen !== null && method_exists($screen, 'is_block_editor') && $screen->is_block_editor();
    }
}
