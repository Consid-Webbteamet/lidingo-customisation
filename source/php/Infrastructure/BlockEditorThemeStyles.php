<?php

declare(strict_types=1);

namespace LidingoCustomisation\Infrastructure;

/**
 * Lets the block editor read theme editor styles from disk instead of over HTTP.
 *
 * WordPress core fetches absolute editor style URLs with a loopback request.
 * Behind an IP allowlist that request fails silently, which leaves the editor
 * canvas without component styles. Theme-relative paths are read from disk.
 */
class BlockEditorThemeStyles
{
    /** @var array<int|string, mixed>|null */
    private ?array $originalEditorStyles = null;

    /** Register hooks around the block editor settings build. */
    public function addHooks(): void
    {
        add_action('enqueue_block_editor_assets', [$this, 'useThemeFilePaths'], PHP_INT_MAX);
        add_filter('block_editor_settings_all', [$this, 'restoreEditorStyles'], PHP_INT_MIN);
    }

    /** Replace theme style URLs with theme-relative paths while core builds editor settings. */
    public function useThemeFilePaths(): void
    {
        global $editor_styles;

        if (!is_array($editor_styles) || $editor_styles === [] || $this->originalEditorStyles !== null) {
            return;
        }

        $themeDirectories = $this->getThemeDirectories();
        $resolvedStyles = $editor_styles;
        $hasChanges = false;

        foreach ($resolvedStyles as $index => $style) {
            if (!is_string($style)) {
                continue;
            }

            $relativePath = $this->toThemeRelativePath($style, $themeDirectories);
            if ($relativePath === null) {
                continue;
            }

            $resolvedStyles[$index] = $relativePath;
            $hasChanges = true;
        }

        if (!$hasChanges) {
            return;
        }

        $this->originalEditorStyles = $editor_styles;
        $editor_styles = $resolvedStyles;
    }

    /**
     * Restore the original editor styles so other consumers keep versioned URLs.
     *
     * @param mixed $settings
     * @return mixed
     */
    public function restoreEditorStyles($settings)
    {
        global $editor_styles;

        if ($this->originalEditorStyles !== null) {
            $editor_styles = $this->originalEditorStyles;
            $this->originalEditorStyles = null;
        }

        return $settings;
    }

    /** @return array<string, string> Theme URL prefixes without scheme, mapped to theme directories. */
    private function getThemeDirectories(): array
    {
        $directories = [
            $this->withoutScheme(get_stylesheet_directory_uri()) => get_stylesheet_directory(),
            $this->withoutScheme(get_template_directory_uri()) => get_template_directory(),
        ];

        $prefixes = [];
        foreach ($directories as $url => $directory) {
            $prefixes[trailingslashit($url)] = untrailingslashit($directory);
        }

        return $prefixes;
    }

    /** @param array<string, string> $themeDirectories */
    private function toThemeRelativePath(string $style, array $themeDirectories): ?string
    {
        if (!preg_match('~^(https?:)?//~i', $style)) {
            return null;
        }

        $url = $this->withoutScheme((string) strtok($style, '?#'));

        foreach ($themeDirectories as $urlPrefix => $directory) {
            if (!str_starts_with($url, $urlPrefix)) {
                continue;
            }

            $relativePath = rawurldecode(substr($url, strlen($urlPrefix)));
            if ($relativePath === '' || str_contains($relativePath, '..')) {
                return null;
            }

            $expectedFile = $directory . '/' . $relativePath;
            if (!is_file($expectedFile) || wp_normalize_path(get_theme_file_path($relativePath)) !== wp_normalize_path($expectedFile)) {
                return null;
            }

            return $relativePath;
        }

        return null;
    }

    private function withoutScheme(string $url): string
    {
        return (string) preg_replace('~^(https?:)?//~i', '//', $url);
    }
}
