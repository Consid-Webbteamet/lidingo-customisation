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

    private function shouldBlockViewData(): bool
    {
        if (!function_exists('get_field') || !get_field('rekai_enable', 'options')) {
            return false;
        }

        $scriptUrl = get_field('rekai_script_url', 'options');
        if (!is_string($scriptUrl) || trim($scriptUrl) === '') {
            return false;
        }

        $host = (string) parse_url((string) home_url(), PHP_URL_HOST);
        if ($host !== '' && str_contains(strtolower($host), 'muniprod.')) {
            return true;
        }

        return !$this->isProductionEnvironment();
    }

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
