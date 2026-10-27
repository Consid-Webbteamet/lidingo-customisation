<?php

declare(strict_types=1);

namespace LidingoCustomisation\Typography;

class BrandFonts
{
    /** Register package-owned font faces for the frontend and block editor. */
    public function addHooks(): void
    {
        add_filter('wp_theme_json_data_theme', [$this, 'addThemeFonts'], 30, 1);
        add_filter('wp_theme_json_data_user', [$this, 'overrideUserFonts'], 30, 1);
    }

    /** Add the brand families without removing other theme fonts. */
    public function addThemeFonts(\WP_Theme_JSON_Data $themeJson): \WP_Theme_JSON_Data
    {
        $data = $themeJson->get_data();
        $families = $data['settings']['typography']['fontFamilies'] ?? [];
        $themeFamilies = [];

        foreach ($families['theme'] ?? [] as $family) {
            $themeFamilies[$family['slug']] = $family;
        }

        $families['theme'] = array_values(array_replace($themeFamilies, $this->getBrandFonts()));

        return $this->updateFamilies($themeJson, $families);
    }

    /** Keep existing user presets, but serve brand fonts from this package. */
    public function overrideUserFonts(\WP_Theme_JSON_Data $themeJson): \WP_Theme_JSON_Data
    {
        $data = $themeJson->get_data();
        $families = $data['settings']['typography']['fontFamilies'] ?? [];
        $brandFonts = $this->getBrandFonts();

        foreach ($families as &$originFamilies) {
            foreach ($originFamilies as &$family) {
                $slug = $family['slug'];
                if (isset($brandFonts[$slug])) {
                    $family = $brandFonts[$slug];
                }
            }
            unset($family);
        }
        unset($originFamilies);

        return $this->updateFamilies($themeJson, $families);
    }

    /** Use the production family names and weight range. */
    private function getBrandFonts(): array
    {
        $fonts = [];

        foreach (['regular', 'medium'] as $variant) {
            $family = 'lidingological-' . $variant;
            $fonts[$family] = [
                'name' => $family,
                'slug' => $family,
                'fontFamily' => '"' . $family . '", sans-serif',
                'fontFace' => [[
                    'fontFamily' => $family,
                    'fontStyle' => 'normal',
                    'fontWeight' => '100 900',
                    'fontDisplay' => 'swap',
                    'src' => [LIDINGO_CUSTOMISATION_URL . 'source/assets/fonts/' . $family . '.woff2'],
                ]],
            ];
        }

        return $fonts;
    }

    private function updateFamilies(\WP_Theme_JSON_Data $themeJson, array $families): \WP_Theme_JSON_Data
    {
        return $themeJson->update_with([
            'version' => \WP_Theme_JSON::LATEST_SCHEMA,
            'settings' => ['typography' => ['fontFamilies' => $families]],
        ]);
    }
}
