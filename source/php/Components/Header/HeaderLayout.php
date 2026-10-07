<?php

declare(strict_types=1);

namespace LidingoCustomisation\Components\Header;

class HeaderLayout
{
    /** Keep Lidingo's header in the existing lower row on both device layouts. */
    public function addHooks(): void
    {
        // Municipio renders an upper row when its item list is non-empty.
        // Filter the settings rather than hiding rendered navigation with CSS.
        add_filter('theme_mod_header_sortable_section_main_upper', '__return_empty_array');
        add_filter('theme_mod_header_sortable_section_main_upper_responsive', '__return_empty_array');
    }
}
