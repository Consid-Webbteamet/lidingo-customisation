@php
    $formatOccasion = static function ($occasion): array {
        $startValue = is_object($occasion) && method_exists($occasion, 'getStartDate')
            ? (string) $occasion->getStartDate()
            : '';
        $endValue = is_object($occasion) && method_exists($occasion, 'getEndTime')
            ? (string) $occasion->getEndTime()
            : '';
        $startDateTime = !empty($startValue) ? date_create_immutable($startValue, wp_timezone()) : false;

        $date = $startDateTime instanceof \DateTimeInterface
            ? wp_date((string) get_option('date_format', 'j F Y'), $startDateTime->getTimestamp())
            : $startValue;
        $startTime = $startDateTime instanceof \DateTimeInterface
            ? $startDateTime->format('H.i')
            : str_replace(':', '.', $startValue);
        $endTime = $endValue !== '' ? str_replace(':', '.', $endValue) : '';
        $time = trim($startTime . ($endTime !== '' ? ' - ' . $endTime : ''));

        return [
            'date' => $date,
            'time' => $time,
        ];
    };
@endphp

@card([
    'heading' => $lang->occasionsTitle
])
    @slot('aboveContent')
        @if(!empty($currentOccasion))
            @php($currentOccasionParts = $formatOccasion($currentOccasion))

            <div class="c-event-page__occasion">
                <div class="c-event-page__occasion-row" style="gap: 0.5rem;">
                    <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; font-family: 'Lidingo Logical', sans-serif; font-size: 1.125rem; font-weight: 400; line-height: 1;">:kalender:</span>

                    @typography([
                        'element' => 'span',
                        'classList' => [
                            'c-event-page__occasion-text',
                            'c-event-page__occasion-text--date',
                            'u-bold'
                        ]
                    ])
                        {!! $currentOccasionParts['date'] !!}
                    @endtypography
                </div>

                @if(!empty($currentOccasionParts['time']))
                    <div class="c-event-page__occasion-row" style="gap: 0.5rem;">
                        <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; font-family: 'Lidingo Logical', sans-serif; font-size: 1.125rem; font-weight: 400; line-height: 1;">:klocka:</span>

                        @typography([
                            'element' => 'span',
                            'classList' => [
                                'c-event-page__occasion-text',
                                'c-event-page__occasion-text--time',
                                'u-bold'
                            ]
                        ])
                            {!! $currentOccasionParts['time'] !!}
                        @endtypography
                    </div>
                @endif
            </div>
        @endif

        @if(!empty($occasions) && count($occasions) > 1)
            @accordion([])
                @accordion__item([
                    'heading' => $lang->moreOccasions
                ])
                    @collection([
                        'compact' => true
                    ])
                    @foreach($occasions as $occasion)
                        @if(!$occasion->isCurrent())
                            @php($occasionParts = $formatOccasion($occasion))

                            @collection__item([
                                'link' => $occasion->getUrl(),
                                'icon' => 'chevron_forward',
                                'iconLast' => true
                            ])
                                @typography([
                                    'element' => 'span',
                                    'classList' => [
                                        'c-event-page__occasion-listing-date'
                                    ]
                                ])
                                    {!! $occasionParts['date'] !!}@if(!empty($occasionParts['time'])) - {!! $occasionParts['time'] !!}@endif
                                @endtypography
                            @endcollection__item
                        @endif
                    @endforeach
                    @endcollection
                @endaccordion__item
            @endaccordion
        @endif
    @endslot
@endcard
