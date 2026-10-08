<?php

declare(strict_types=1);

namespace LidingoCustomisation\Presentation;

class BrandTokens
{
    private array $brandTokens;

    public function __construct()
    {
        $this->brandTokens = json_decode(
            file_get_contents(LIDINGO_CUSTOMISATION_PATH . 'source/tokens/lidingo-brand.tokens.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['token'];
    }

    /** Supply code-owned globals without seeding or rewriting stored settings. */
    public function addHooks(): void
    {
        add_filter('theme_mod_tokens', [$this, 'mergeBrandTokens']);
        add_filter('pre_set_theme_mod_tokens', [$this, 'mergeBrandTokens']);
        add_filter('Municipio/Styleguide/Customize/TokenData', [$this, 'lockBrandFields']);
    }

    /** Preserve component scopes and other settings while protecting brand globals. */
    public function mergeBrandTokens(mixed $value): string
    {
        $tokens = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($tokens)) {
            $tokens = [];
        }

        $tokens['token'] = array_replace($tokens['token'] ?? [], $this->brandTokens);
        $tokens['component'] = $tokens['component'] ?? new \stdClass();

        return wp_json_encode($tokens);
    }

    /** Show native brand controls as locked in Design Builder. */
    public function lockBrandFields(array $tokenData): array
    {
        foreach ($tokenData['categories'] as &$category) {
            foreach ($category['settings'] as &$setting) {
                if (array_key_exists($setting['variable'], $this->brandTokens)) {
                    $setting['locked'] = true;
                }
            }
            unset($setting);
        }
        unset($category);

        return $tokenData;
    }
}
