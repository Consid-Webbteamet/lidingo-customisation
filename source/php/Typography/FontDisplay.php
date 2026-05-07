<?php

declare(strict_types=1);

namespace LidingoCustomisation\Typography;

class FontDisplay
{
    public function addHooks(): void
    {
        add_filter('wp_theme_json_data_theme', [$this, 'addSwapToFontFaces'], 20, 1);
        add_filter('wp_theme_json_data_user', [$this, 'addSwapToFontFaces'], 20, 1);
    }

    /** Force Customizer and theme font faces to render with font-display swap. */
    public function addSwapToFontFaces(\WP_Theme_JSON_Data $themeJson): \WP_Theme_JSON_Data
    {
        $data = $themeJson->get_data();
        $fontFamilies = $data['settings']['typography']['fontFamilies'] ?? null;

        if (!is_array($fontFamilies)) {
            return $themeJson;
        }

        $hasChanges = false;

        foreach ($fontFamilies as &$originFontFamilies) {
            if (!is_array($originFontFamilies)) {
                continue;
            }

            foreach ($originFontFamilies as &$fontFamily) {
                if (empty($fontFamily['fontFace']) || !is_array($fontFamily['fontFace'])) {
                    continue;
                }

                foreach ($fontFamily['fontFace'] as &$fontFace) {
                    if (!is_array($fontFace) || ($fontFace['fontDisplay'] ?? null) === 'swap') {
                        continue;
                    }

                    $fontFace['fontDisplay'] = 'swap';
                    $hasChanges = true;
                }
                unset($fontFace);
            }
            unset($fontFamily);
        }
        unset($originFontFamilies);

        if (!$hasChanges) {
            return $themeJson;
        }

        return $themeJson->update_with([
            'version' => (int) ($data['version'] ?? \WP_Theme_JSON::LATEST_SCHEMA),
            'settings' => [
                'typography' => [
                    'fontFamilies' => $fontFamilies,
                ],
            ],
        ]);
    }
}
