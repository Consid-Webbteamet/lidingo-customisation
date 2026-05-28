@if((!empty($bookingLink) || !empty($priceListItems)) && !$eventIsInThePast)
    @card([
        'heading' => $lang->bookingTitle
    ])
        @slot('aboveContent')
            <div class="c-event-page__booking-content">
                @if(!empty($priceListItems))
                    @listing([
                        'list' => array_map(function($priceListItem) {
                            return [ 'label' => $priceListItem->getName() . ': ' . $priceListItem->getPrice() ];
                        }, $priceListItems)
                    ])
                    @endlisting
                @endif

                @if(!empty($bookingLink))
                    @typography([
                        'element' => 'span',
                        'variant' => 'meta',
                        'classList' => [
                            'c-event-page__booking-disclaimer'
                        ],
                    ])
                        {!! $lang->bookingDisclaimer !!}
                    @endtypography

                    @button([
                        'href' => $bookingLink,
                        'color' => 'primary',
                        'style' => 'filled',
                        'size' => 'md',
                        'fullWidth' => false,
                        'classList' => [
                            'c-event-page__booking-button'
                        ],
                        'target' => '_blank'
                    ])
                        <span class="c-event-page__booking-button-content">
                            <span class="c-button__label-text">{{ $lang->bookingButton }}</span>
                            <span aria-hidden="true" class="c-event-page__booking-button-icon"></span>
                        </span>
                    @endbutton
                @endif
            </div>
        @endslot
    @endcard
@endif
