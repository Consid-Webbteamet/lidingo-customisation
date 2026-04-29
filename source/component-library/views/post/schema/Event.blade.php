@php
    $eventSchedules = method_exists($post, 'getSchemaProperty') ? $post->getSchemaProperty('eventSchedule') : null;
    $eventSchedules = is_array($eventSchedules) ? $eventSchedules : (!empty($eventSchedules) ? [$eventSchedules] : []);
    usort($eventSchedules, static fn ($a, $b) => ($a?->getProperty('startDate') ?? null) <=> ($b?->getProperty('startDate') ?? null));

    $now = new \DateTime('now');
    $upcomingSchedules = array_values(array_filter($eventSchedules, static fn ($schedule) => $schedule?->getProperty('startDate') instanceof \DateTimeInterface && $schedule->getProperty('startDate') >= $now));
    $pastSchedules = array_reverse(array_values(array_filter($eventSchedules, static fn ($schedule) => $schedule?->getProperty('startDate') instanceof \DateTimeInterface && $schedule->getProperty('startDate') < $now)));
    $selectedSchedule = $upcomingSchedules[0] ?? $pastSchedules[0] ?? null;
    $selectedStartDate = $selectedSchedule?->getProperty('startDate');
    $selectedEndDate = $selectedSchedule?->getProperty('endDate');

    $eventDate = $selectedStartDate instanceof \DateTimeInterface ? wp_date(\Municipio\Helper\DateFormat::getDateFormat('date'), $selectedStartDate->getTimestamp()) : null;
    $eventTime = $selectedStartDate instanceof \DateTimeInterface ? wp_date(\Municipio\Helper\DateFormat::getDateFormat('time'), $selectedStartDate->getTimestamp()) : null;

    if ($eventTime && $selectedEndDate instanceof \DateTimeInterface) {
        $eventTime .= ' - ' . wp_date(\Municipio\Helper\DateFormat::getDateFormat('time'), $selectedEndDate->getTimestamp());
    }
@endphp

@segment([
    'layout'            => 'card',
    'image'             => $post->getImage(),
    'link'              => $getSchemaEventPermalink($post),
    'containerAware'    => true,
    'attributeList'     => ['data-js-posts-list-item' => true],
])
    @if(!empty($getSchemaEventDateBadgeDate($post)))
        @slot('floating')
            @datebadge([ 'date' => $getSchemaEventDateBadgeDate($post), 'size' => 'sm']) @enddatebadge
        @endslot
    @endif
    @slot('aboveContent')
        @typography(['element' => 'h2', 'variant' => 'h4', 'classList' => ['u-margin__top--0']])
            {!!$post->getTitle()!!}
        @endtypography

        @element(['classList' => ['u-margin__bottom--0', 'u-margin__top--2', 'u-display--flex', 'u-flex-direction--column', 'o-layout-grid--gap-1']])
            @if(!empty($getSchemaEventPlaceName($post)))
                @typography(['variant' => 'meta', 'classList' => ['u-margin__top--0', 'u-margin__bottom--0', 'u-display--flex', 'u-align-items--center', 'o-layout-grid--gap-1']])
                    @icon(['icon' => 'location_on', 'size' => 'sm'])@endicon
                    {!! $getSchemaEventPlaceName($post) !!}
                @endtypography
            @endif
            @if(!empty($eventDate))
                @typography(['variant' => 'meta', 'classList' => ['u-margin__top--0', 'u-margin__bottom--0', 'u-display--flex', 'u-align-items--center', 'o-layout-grid--gap-1']])
                    @icon(['icon' => 'event', 'size' => 'sm'])@endicon
                    {!! $eventDate !!}
                    @if(!empty($eventTime))
                        @icon(['icon' => ':klocka:', 'size' => 'sm'])@endicon
                        {!! $eventTime !!}
                    @endif
                @endtypography
            @endif
        @endelement

        @if(!empty($getSchemaEventHasMoreOccasions($post)))
            @element([
                'classList' => ['u-margin__top--2', 'u-padding__x--1', 'u-border--1', 'u-color__text--primary', 'u-position--relative', 'u-preloader--no-border'],
                'attributeList' => [ 'style' => 'border-radius: 8px; display: inline-block;' ]
            ])
                @typography(['element' => 'span', 'variant' => 'meta'])
                    {{ $getEventMoreOccasionsLabel() }}
                @endtypography
            @endelement
        @endif
    @endslot
@endsegment
