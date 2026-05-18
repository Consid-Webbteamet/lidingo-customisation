<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\HeaderSearch;

class HeaderSearchOverrides
{
    /** Register the header search overrides. */
    public function addHooks(): void
    {
        add_filter('ComponentLibrary/Component/Data', [$this, 'overridePlaceholder'], 20, 1);
    }

    /** Replace the collapsible header search placeholder. */
    public function overridePlaceholder(array $data): array
    {
        if (($data['name'] ?? '') !== 's') {
            return $data;
        }

        if (($data['type'] ?? '') !== 'text') {
            return $data;
        }

        if (($data['hideLabel'] ?? false) !== true) {
            return $data;
        }

        if (($data['icon']['icon'] ?? null) !== '') {
            return $data;
        }

        $data['placeholder'] = __('Sök', 'lidingo-customisation');

        return $data;
    }
}
