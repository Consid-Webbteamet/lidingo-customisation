@php
    $eventCardDate = \LidingoCustomisation\Components\Posts\EventCardDate::resolve($post);
    $eventLink = $eventCardDate['link'];
    $eventBadgeDate = $eventCardDate['badgeDate'];
    $eventDate = $eventCardDate['date'];
    $eventTime = $eventCardDate['time'];
@endphp

@segment([
    'layout'            => 'card',
    'image'             => $post->getImage(),
    'link'              => $eventLink,
    'containerAware'    => true,
    'attributeList'     => ['data-js-posts-list-item' => true],
])
    @if(!empty($eventBadgeDate))
        @slot('floating')
            @datebadge([ 'date' => $eventBadgeDate, 'size' => 'sm']) @enddatebadge
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
