@if (!empty($mobileMenu['items']))
@drawer([
    'toggleButtonData' => [
        'id' => 'mobile-menu-trigger-open',
        'color' => $customizer->headerTriggerButtonColor,
        'style' => $customizer->headerTriggerButtonType,
        'size' => $customizer->headerTriggerButtonSize,
        'icon' => 'toggleAriaPressedHamburgerClose',
        'context' => ['site.header.menutrigger', 'site.header.casual.menutrigger'],
        'classList' => ['mobile-menu-trigger', 'u-order--10', 's-header-button'],
        'text' => $lang->menu,
        'reversePositions' => true,
        'toggle' => true,
        'attributeList' => [
            'data-toggle-icon' => 'close',
            'data-js-toggle-group' => 'drawer'
        ]
    ],
    'id' => 'drawer',
    'attributeList' => ['data-move-to' => 'body', 'data-js-toggle-item' => 'drawer'],
    'classList' => [
        'c-drawer--' . (!empty($mobileMenu['items'])&&!empty($mobileSecondaryMenu['items']) ? 'duotone' : 'monotone'),
        's-drawer-menu'
    ],
    'label' => $lang->close,
    'screenSizes' => $screenSizes ?? $customizer->drawerScreenSizes,
    'context' => ['site.header.drawer'],
])

    @slot('search')
        @includeWhen(
                $showMobileSearchDrawer,
                'partials.search.drawer-search-form',
                ['classList' => ['search-form', 'u-margin__top--2', 'u-width--100']]
            )
    @endslot

    @if (!empty($mobileMenu['items'])||!empty($mobileSecondaryMenu['items'])) 
    @slot('menu')
        @if (!empty($mobileMenu['promotedItems']))
            <div class="c-drawer__promoted-links">
                @includeIf(
                    'partials.navigation.mobile',
                    [
                        'mobileMenu' => [
                            'items' => $mobileMenu['promotedItems'],
                        ],
                        'classList' => [
                            'c-nav--drawer',
                            'site-nav-mobile__promoted',
                            's-nav-drawer',
                            's-nav-drawer-promoted'
                        ]
                    ]
                )
            </div>
        @endif

        @includeIf(
            'partials.navigation.mobile', 
                [
                    'mobileMenu' => $mobileMenu, 
                    'classList' => [
                        'c-nav--drawer',
                        'site-nav-mobile__primary',
                        's-nav-drawer',
                        's-nav-drawer-primary',
                        !empty($customizer->drawerDivider) ? 'c-nav--bordered' : '',
                        !empty($customizer->drawerDividerTopLevelOnly) ? 'c-nav--bordered-top-level' : ''
                        
                    ]
                ]
            )

                {{-- No ajax in wp-menus, thus not in its own file --}}

            @nav([
                    'id' => 'drawer-menu',
                    'classList' => [
                        'c-nav--drawer',
                        'site-nav-mobile__secondary',
                        's-nav-drawer',
                        's-nav-drawer-secondary',
                        !empty($customizer->drawerDivider) ? 'c-nav--bordered' : '',
                        !empty($customizer->drawerDividerTopLevelOnly) ? 'c-nav--bordered-top-level' : ''
                    ],
                    'items' => $mobileSecondaryMenu['items'],
                    'direction' => 'vertical',
                    'includeToggle' => true,
                    'height' => 'sm',
                    'expandLabel' => $lang->expand,
                    'context' => 'site.mobile-menu'
                ])
            @endnav

            @if (!empty($primaryMenu['items']))
                @nav([
                    'id' => 'drawer-menu-primary',
                    'classList' => [
                        'c-nav--drawer',
                        'site-nav-mobile__primary-extra',
                        's-nav-drawer',
                        's-nav-drawer-primary-extra',
                        !empty($customizer->drawerDivider) ? 'c-nav--bordered' : '',
                        !empty($customizer->drawerDividerTopLevelOnly) ? 'c-nav--bordered-top-level' : ''
                    ],
                    'items' => $primaryMenu['items'],
                    'direction' => 'vertical',
                    'includeToggle' => true,
                    'height' => 'sm',
                    'expandLabel' => $lang->expand,
                    'context' => 'site.mobile-menu'
                ])
                @endnav
            @endif

            @if (!empty($languageMenu['items']))
                <div class="site-language-menu site-language-menu--drawer" data-js-toggle-item="drawer-language-menu-toggle" data-js-toggle-class="is-expanded" data-js-click-away="is-expanded">
                    @button([
                        'id' => 'drawer-site-language-menu-button',
                        'text' => $lang->changeLanguage,
                        'icon' => 'language',
                        'color' => $customizer->headerTriggerButtonColor,
                        'style' => $customizer->headerTriggerButtonType,
                        'size' => $customizer->headerTriggerButtonSize,
                        'reversePositions' => true,
                        'toggle' => true,
                        'classList' => ['site-language-menu-button', 's-header-button'],
                        'attributeList' => [
                            'data-js-toggle-trigger' => 'drawer-language-menu-toggle',
                            'data-toggle-icon' => 'close',
                            'data-js-click-away-remove-pressed' => '',
                            'aria-label' => $lang->changeLanguage,
                        ],
                    ])
                    @endbutton

                    @card([
                        'classList' => [
                            'site-language-menu__card',
                            'u-padding--2',
                            'u-color__bg--default',
                        ]
                    ])
                        @nav([
                            'id' => 'drawer-menu-language',
                            'items' => $languageMenu['items'],
                            'direction' => 'vertical',
                            'includeToggle' => false,
                            'classList' => ['s-nav-language'],
                            'height' => 'md',
                            'expandLabel' => $lang->expand
                        ])
                        @endnav

                        @if(!empty($languageMenuOptions->disclaimer))
                            @typography([
                                'variant' => 'byline',
                                'classList' => [
                                    'u-color__text--dark',
                                    'u-padding--1'
                                ]
                            ])
                                {{ $languageMenuOptions->disclaimer }}
                            @endtypography
                        @endif
                    @endcard
                </div>
            @endif
        @endslot
        @if (!empty($customizer->headerLoginLogoutShowInMobileMenu))
            @slot('afterMenu')
                @include(
                    'partials.header.components.user',
                    [
                        'classList' => ['user--drawer']
                    ]
                )
            @endslot
        @endif
      @else
      {{-- No menu items found --}}
      @endif

@enddrawer  
@endif
