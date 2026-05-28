@card([
    'heading' => $lang->placeTitle,
])
    @php($placeAddressLines = preg_split('/<br\s*\/?>/i', (string) $place['address']) ?: [])
    @php($placeAddressLines = array_values(array_filter(array_map('trim', $placeAddressLines), static fn ($line) => $line !== '')))
    @php($placeAddressPrimary = $placeAddressLines[0] ?? '')
    @php($placeAddressSecondary = array_slice($placeAddressLines, 1))
    @if(!empty($place['lat']) && !empty($place['lng']))
        @slot('beforeContent')
            @map([
                'height' => '250px',
                'markers' => [
                    [
                        'lat' => $place['lat'],
                        'lng' => $place['lng'],
                        'content' => render_blade_view('partials.schema.event.place-marker-content', ['post' => $post])
                    ]
                ],
                'lat' => $place['lat'],
                'lng' => $place['lng'],
                'zoom' => 15
            ])
            @endmap
        @endslot
    @endif

    @if(!empty($place['address']))
        @slot('aboveContent')
            <div class="c-event-page__place-address-block">
                @if(!empty($placeAddressPrimary))
                    @typography([
                        'element' => 'p',
                        'variant' => 'p',
                        'classList' => [
                            'c-card__content',
                            'c-event-page__place-address'
                        ]
                    ])
                        <span class="c-event-page__place-address-icon" aria-hidden="true">:platsnål:</span>
                        <span class="c-event-page__place-address-text">{!! $placeAddressPrimary !!}</span>
                    @endtypography
                @endif

                @if(!empty($placeAddressSecondary))
                    @typography([
                        'element' => 'p',
                        'variant' => 'p',
                        'classList' => [
                            'c-card__content',
                            'c-event-page__place-address-details'
                        ]
                    ])
                        {!! implode('<br>', $placeAddressSecondary) !!}
                    @endtypography
                @endif
            </div>
        @endslot
    @endif

    @if(!empty($place['url']))
        @slot('belowContent')
            @link(['href' => $place['url']])
                {{$lang->directionsLabel}}
            @endlink
        @endslot
    @endif
@endcard
