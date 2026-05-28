@card([
    'heading' => $lang->placeTitle,
])
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
            @typography([
                'element' => 'p',
                'variant' => 'p',
                'classList' => [
                    'c-card__content',
                    'c-event-page__place-address'
                ]
            ])
                <span class="c-event-page__place-address-icon" aria-hidden="true">:platsnål:</span>
                <span class="c-event-page__place-address-text">{!! $place['address'] !!}</span>
            @endtypography
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
