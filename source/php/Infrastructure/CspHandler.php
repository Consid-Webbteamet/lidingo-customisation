<?php

declare(strict_types=1);

namespace LidingoCustomisation\Infrastructure;

class CspHandler
{
    public function __construct(private DevServer $devServer)
    {
    }

    public function addDevServerCspDomains(array $domains): array
    {
        if (!$this->devServer->shouldUseDevServer()) {
            return $domains;
        }

        $devServerHost = $this->devServer->getHostWithPort();

        if ($devServerHost === null) {
            return $domains;
        }

        $requiredByDirective = [
            'script-src' => [$devServerHost],
            'style-src' => [$devServerHost],
            'img-src' => [$devServerHost],
            'connect-src' => [$devServerHost, $this->devServer->getWsOrigin()],
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
        if (!$this->devServer->shouldStripBlockingCspDirectives()) {
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
}
