<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\Sections;

class SectionFullHeadingOverrides
{
    public function addHooks(): void
    {
        add_filter('ComponentLibrary/Component/Typography/Data', [$this, 'disableSectionFullAutopromote'], 20, 1);
    }

    public function disableSectionFullAutopromote(array $data): array
    {
        $classList = $data['classList'] ?? [];

        if (!is_array($classList)) {
            return $data;
        }

        $isSectionFullTitle = in_array('c-segment__title', $classList, true)
            && ($data['element'] ?? null) === 'h2'
            && ($data['variant'] ?? null) === 'h1'
            && ($data['autopromote'] ?? false) === true;

        if (!$isSectionFullTitle) {
            return $data;
        }

        $data['autopromote'] = false;

        return $data;
    }
}
