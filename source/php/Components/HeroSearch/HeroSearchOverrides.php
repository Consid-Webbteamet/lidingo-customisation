<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\HeroSearch;

class HeroSearchOverrides
{
    /** Register the hero search overrides. */
    public function addHooks(): void
    {
        add_filter('ComponentLibrary/Component/Data', [$this, 'overridePlaceholder'], 20, 1);
        add_filter('ComponentLibrary/Component/Button/Data', [$this, 'overrideButtonText'], 20, 1);
    }

    /** Replace the hero search placeholder. */
    public function overridePlaceholder(array $data): array
    {
        if (($data['id'] ?? '') !== 'hero-search-form__field') {
            return $data;
        }

        $data['placeholder'] = 'Sök';

        return $data;
    }

    /** Replace the hero search button text. */
    public function overrideButtonText(array $data): array
    {
        if (($data['id'] ?? '') !== 'hero-search-form__submit') {
            return $data;
        }

        $data['text'] = '';
        $data['icon'] = ':sök:';
        $data['ariaLabel'] = 'Sök';

        return $data;
    }
}
