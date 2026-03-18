<?php

declare(strict_types=1);

namespace LidingoCustomisation\Infrastructure;

class AssetManifest
{
    /**
     * @var string[]
     */
    private array $requiredEntries;

    private string $manifestPath;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $manifest = [];

    private ?string $errorMessage = null;

    public function __construct(string $manifestPath, array $requiredEntries = [])
    {
        $this->manifestPath = $manifestPath;
        $this->requiredEntries = $requiredEntries;
        $this->load();
    }

    public function isLoaded(): bool
    {
        return !empty($this->manifest);
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getAssetUrl(string $entry, string $distUrl): ?string
    {
        $assetPath = $this->getAssetPath($entry);

        if ($assetPath === null) {
            return null;
        }

        return trailingslashit($distUrl) . ltrim($assetPath, '/');
    }

    private function getAssetPath(string $entry): ?string
    {
        if (!$this->isLoaded()) {
            return null;
        }

        if (!isset($this->manifest[$entry]) || !is_array($this->manifest[$entry])) {
            return null;
        }

        $entryData = $this->manifest[$entry];

        if (!isset($entryData['file']) || !is_string($entryData['file'])) {
            return null;
        }

        return $entryData['file'];
    }

    private function load(): void
    {
        if (!file_exists($this->manifestPath)) {
            $this->errorMessage = sprintf(
                'Lidingo Customisation manifest saknas: %s',
                $this->manifestPath
            );

            return;
        }

        $json = file_get_contents($this->manifestPath);

        if (!is_string($json) || $json === '') {
            $this->errorMessage = sprintf(
                'Lidingo Customisation kunde inte läsa manifest: %s',
                $this->manifestPath
            );

            return;
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            $this->errorMessage = sprintf(
                'Lidingo Customisation manifest har ogiltigt JSON-format: %s',
                $this->manifestPath
            );

            return;
        }

        $missingEntries = array_values(array_filter(
            $this->requiredEntries,
            fn (string $entry): bool => !isset($decoded[$entry]) || !is_array($decoded[$entry])
        ));

        if (!empty($missingEntries)) {
            $this->errorMessage = sprintf(
                'Lidingo Customisation manifest saknar entr%1$s: %2$s',
                count($missingEntries) === 1 ? 'y' : 'ier',
                implode(', ', $missingEntries)
            );

            return;
        }

        $this->manifest = $decoded;
    }
}
