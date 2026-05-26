<?php

declare(strict_types=1);

namespace LidingoCustomisation\Integrations\RekAi;

class RekAiIntegration
{
    /** Register RekAI hooks. */
    public function addHooks(): void
    {
        add_action('wp_head', [$this, 'setTestEnvironmentBlockers'], 1);
        add_action('template_redirect', [$this, 'startRecommendScriptBuffer'], 0);
    }

    /**
     * Block RekAI view tracking in test environments while still allowing predictions.
     *
     * Rek.ai recommends setting a short-lived "rekblock" cookie in test to avoid
     * storing page views while still allowing recommendations to be fetched.
     */
    public function setTestEnvironmentBlockers(): void
    {
        if (is_admin() || !$this->shouldBlockViewData()) {
            return;
        }

        echo "<script>document.cookie='rekblock=1; max-age=60; SameSite=None; Secure';window.rek_blocksaveview=true;</script>\n";
    }

    /**
     * Work around invalid inline JS from modularity-recommend when output is compressed.
     *
     * Keep this patch local and easy to remove once the upstream plugin emits
     * semicolon-safe JavaScript.
     */
    public function startRecommendScriptBuffer(): void
    {
        if (!$this->shouldPatchRecommendScriptForRequest()) {
            return;
        }

        ob_start([$this, 'fixCompressedRecommendScript']);
    }

    /** Repair missing semicolons in modularity-recommend's compressed inline script. */
    public function fixCompressedRecommendScript(string $html): string
    {
        if (
            !str_contains($html, 'modularity-mod-recommend') ||
            !str_contains($html, 'window.__rekai.predict')
        ) {
            return $html;
        }

        return strtr(
            $html,
            [
                'var advancedOptions = {} var rekaiOptions = {}' => 'var advancedOptions = {}; var rekaiOptions = {};',
                'advancedOptions = JSON.parse("null") rekaiOptions =' => 'advancedOptions = JSON.parse("null"); rekaiOptions =',
                '})) } catch (error)' => '})); } catch (error)',
                '}, } window.__rekai.predict(options, renderHtml);' => '}, }; window.__rekai.predict(options, renderHtml);',
            ]
        );
    }

    /** Detect whether view tracking should be blocked in this environment. */
    private function shouldBlockViewData(): bool
    {
        if ($this->isMuniprodHost()) {
            return true;
        }

        if (!function_exists('get_field') || !get_field('rekai_enable', 'options')) {
            return false;
        }

        $scriptUrl = get_field('rekai_script_url', 'options');
        if (!is_string($scriptUrl) || trim($scriptUrl) === '') {
            return false;
        }

        return !$this->isProductionEnvironment();
    }

    /** Only buffer frontend requests that actually render a recommend module. */
    private function shouldPatchRecommendScriptForRequest(): bool
    {
        if (
            is_admin() ||
            wp_doing_ajax() ||
            (defined('REST_REQUEST') && REST_REQUEST) ||
            is_feed()
        ) {
            return false;
        }

        $postId = get_queried_object_id();
        if ($postId <= 0) {
            return false;
        }

        return $this->hasRecommendModule(get_post_meta($postId, 'modularity-modules', true));
    }

    /** Recursively detect a modularity recommend module entry. */
    private function hasRecommendModule(mixed $modules): bool
    {
        if (!is_array($modules)) {
            return false;
        }

        if (($modules['name'] ?? null) === 'mod-recommend') {
            return true;
        }

        foreach ($modules as $module) {
            if ($this->hasRecommendModule($module)) {
                return true;
            }
        }

        return false;
    }

    /** Keep RekAI view tracking blocked on the replacement production host. */
    private function isMuniprodHost(): bool
    {
        $hosts = [
            (string) wp_parse_url((string) home_url(), PHP_URL_HOST),
            isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_HOST'])) : '',
        ];

        foreach ($hosts as $host) {
            $normalizedHost = strtolower(trim($host));

            if ($normalizedHost !== '' && str_starts_with($normalizedHost, 'muniprod.')) {
                return true;
            }
        }

        return false;
    }

    /** Treat any non-production environment as test mode. */
    private function isProductionEnvironment(): bool
    {
        if (defined('WP_ENV') && is_string(WP_ENV)) {
            return strtolower(WP_ENV) === 'production';
        }

        if (function_exists('wp_get_environment_type')) {
            return wp_get_environment_type() === 'production';
        }

        return false;
    }
}
