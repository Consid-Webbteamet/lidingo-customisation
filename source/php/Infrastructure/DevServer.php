<?php

declare(strict_types=1);

namespace LidingoCustomisation\Infrastructure;

class DevServer
{
    private ?bool $reachable = null;

    /** Determine whether to use the Vite dev server. */
    public function shouldUseDevServer(): bool
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

    /** Determine whether to strip blocking CSP directives. */
    public function shouldStripBlockingCspDirectives(): bool
    {
        return $this->shouldUseDevServer() || $this->isLocalHttpDevelopmentMode();
    }

    /** Return the configured dev server origin. */
    public function getOrigin(): string
    {
        $origin = (string) apply_filters(
            'lidingo_customisation/dev_server_origin',
            'http://localhost:5173'
        );

        return rtrim($origin, '/');
    }

    /** Return the dev server host and port. */
    public function getHostWithPort(): ?string
    {
        $parsedOrigin = parse_url($this->getOrigin());

        if (!is_array($parsedOrigin) || empty($parsedOrigin['host'])) {
            return null;
        }

        $host = strtolower((string) $parsedOrigin['host']);

        if (isset($parsedOrigin['port'])) {
            $host .= ':' . (int) $parsedOrigin['port'];
        }

        return $host;
    }

    /** Return the websocket origin for the dev server. */
    public function getWsOrigin(): ?string
    {
        $parsedOrigin = parse_url($this->getOrigin());

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

    /** Probe the dev server client endpoint once per request. */
    private function isDevServerReachable(): bool
    {
        if ($this->reachable !== null) {
            return $this->reachable;
        }

        $response = wp_remote_get(
            $this->getOrigin() . '/@vite/client',
            [
                'timeout' => 0.6,
                'sslverify' => false,
            ]
        );

        if (is_wp_error($response)) {
            $this->reachable = false;
            return false;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $this->reachable = $statusCode === 200;

        return $this->reachable;
    }

    /** Detect local HTTP development mode without the dev server. */
    private function isLocalHttpDevelopmentMode(): bool
    {
        if (!defined('WP_ENV') || WP_ENV !== 'development') {
            return false;
        }

        $homeScheme = strtolower((string) parse_url(home_url('/'), PHP_URL_SCHEME));

        return $homeScheme === 'http';
    }
}
