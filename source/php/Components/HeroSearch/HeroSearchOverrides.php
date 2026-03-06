<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\HeroSearch;

class HeroSearchOverrides
{
    public function addHooks(): void
    {
        add_filter('ComponentLibrary/Component/Data', [$this, 'overridePlaceholder'], 20, 1);
        add_filter('ComponentLibrary/Component/Button/Data', [$this, 'overrideButtonText'], 20, 1);
    }

    public function overridePlaceholder(array $data): array
    {
        if (($data['id'] ?? '') !== 'hero-search-form__field') {
            return $data;
        }

        $data['placeholder'] = 'Sök';

        return $data;
    }

    public function overrideButtonText(array $data): array
    {
        if (($data['id'] ?? '') !== 'hero-search-form__submit') {
            return $data;
        }

        $data['text'] = ':sök:';
        $data['ariaLabel'] = 'Sök';

        return $data;
    }
}
