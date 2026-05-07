<?php

declare(strict_types=1);

namespace LidingoCustomisation\Integrations\RekAi;

class RekAiIntegration
{
    /** Register RekAI hooks. */
    public function addHooks(): void
    {
        add_action('wp_head', [$this, 'setTestEnvironmentBlockers'], 1);
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
