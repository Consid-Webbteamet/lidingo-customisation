<div class="{{ trim('c-content-page__aside-inner c-job-sidebar ' . ($className ?? '')) }}">
    @if(!empty($informationItems))
        <div class="o-grid-12">
            @card(['classList' => ['c-card--panel', 'c-job-sidebar__card']])
                @collection()
                    @collection__item([])
                        @typography(['element' => 'h2', 'variant' => 'h2'])
                            {{ __('Information', 'lidingo-customisation') }}
                        @endtypography
                    @endcollection__item

                    @foreach($informationItems as $item)
                        @collection__item([])
                            @typography(['element' => 'h3', 'variant' => 'h4'])
                                {{ $item['label'] }}
                            @endtypography
                            @typography(['element' => 'p'])
                                {{ $item['value'] }}
                            @endtypography
                        @endcollection__item
                    @endforeach
                @endcollection
            @endcard
        </div>
    @endif

    @if(!empty($contacts))
        <div class="o-grid-12">
            @card(['classList' => ['c-card--panel', 'c-job-sidebar__card']])
                @collection()
                    @collection__item([])
                        @typography(['element' => 'h2', 'variant' => 'h2'])
                            {{ __('Kontakt', 'lidingo-customisation') }}
                        @endtypography
                    @endcollection__item

                    @foreach($contacts as $contact)
                        @php($name = (string) ($contact['name'] ?? ''))
                        @php($position = (string) ($contact['position'] ?? ''))
                        @php($phone = (string) ($contact['phone'] ?? ''))
                        @php($phoneSanitized = (string) ($contact['phone_sanitized'] ?? preg_replace('/\D/', '', $phone)))
                        @php($email = (string) ($contact['email'] ?? ''))

                        @if(!empty($name) || !empty($position) || !empty($phone) || !empty($email))
                            @collection__item([])
                                @if(!empty($name))
                                    @typography(['element' => 'h3', 'variant' => 'h4'])
                                        {{ $name }}
                                    @endtypography
                                @endif

                                @if(!empty($position))
                                    @typography(['variant' => 'meta', 'element' => 'span'])
                                        {{ $position }}
                                    @endtypography
                                @endif

                                @if(!empty($phone))
                                    @typography(['element' => 'p'])
                                        <span class="c-job-sidebar__contact-token" aria-hidden="true">:telefon:</span>
                                        <a href="tel:{{ $phoneSanitized }}">{{ $phone }}</a>
                                    @endtypography
                                @endif

                                @if(!empty($email))
                                    @typography(['element' => 'p'])
                                        <span class="c-job-sidebar__contact-token" aria-hidden="true">:mail:</span>
                                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                                    @endtypography
                                @endif
                            @endcollection__item
                        @endif
                    @endforeach
                @endcollection
            @endcard
        </div>
    @endif

    <div class="o-grid-12">
        @if($isExpired || empty($applyLink))
            @button([
                'text' => __('Ansökningstiden har gått ut', 'lidingo-customisation'),
                'style' => 'filled',
                'attributeList' => ['disabled' => 'true']
            ])
            @endbutton
        @else
            @button([
                'text' => __('Ansök nu', 'lidingo-customisation'),
                'color' => 'primary',
                'style' => 'filled',
                'href' => $applyLink,
                'icon' => ':höger:',
                'classList' => ['c-button--margin-top', 'u-margin__right--1']
            ])
            @endbutton
        @endif
    </div>
</div>
