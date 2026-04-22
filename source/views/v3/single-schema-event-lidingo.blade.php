@extends('templates.grid')

@section('layout')
    @php($eventSchema = method_exists($post, 'getSchema') ? $post->getSchema() : null)
    @php($attendanceMode = is_object($eventSchema) && method_exists($eventSchema, 'getProperty') ? (string) ($eventSchema->getProperty('eventAttendanceMode') ?? '') : '')
    @php($showPlaceCard = str_contains($attendanceMode, 'OfflineEventAttendanceMode'))
    @php($eventTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))
    @php($eventImage = method_exists($post, 'getImage') ? $post->getImage() : null)
    @php($eventImageSrc = is_object($eventImage) && method_exists($eventImage, 'getUrl') ? $eventImage->getUrl() : $eventImage)
    @php($eventImageSrcset = is_object($eventImage) && method_exists($eventImage, 'getSrcSet') ? $eventImage->getSrcSet() : false)
    @php($eventImageAlt = is_object($eventImage) && method_exists($eventImage, 'getAltText') ? $eventImage->getAltText() : null)
    @php($eventImageFocusPoint = is_object($eventImage) && method_exists($eventImage, 'getFocusPoint') ? $eventImage->getFocusPoint() : null)
    @php($eventImageStyle = is_array($eventImageFocusPoint) && isset($eventImageFocusPoint['left'], $eventImageFocusPoint['top']) ? sprintf('object-position: %s%% %s%%;', $eventImageFocusPoint['left'], $eventImageFocusPoint['top']) : null)

    @section('above-content')
        @parent

        <div class="c-event-page__helper u-print-display--none">
            @includeIf('partials.navigation.breadcrumb')
        </div>

        <div class="c-event-page">
            <div class="c-event-page__article">
                @if (!empty($eventImage))
                    <div class="c-event-page__hero">
                        @image([
                            'src' => $eventImageSrc,
                            'srcset' => $eventImageSrcset,
                            'alt' => $eventImageAlt,
                            'rounded' => false,
                            'calculateAspectRatio' => false,
                            'imgAttributeList' => array_filter([
                                'sizes' => '(min-width: 1400px) 1400px, 100vw',
                                'style' => $eventImageStyle,
                            ]),
                            'classList' => ['c-event-page__hero-image'],
                        ])
                        @endimage

                        @if (!empty($currentOccasion))
                            <div class="c-event-page__badge c-event-page__badge--overlay">
                                @datebadge(['date' => $currentOccasion->getStartDate()])
                                @enddatebadge
                            </div>
                        @endif
                    </div>
                @endif

                @if (!empty($currentOccasion) && empty($eventImage))
                    <div class="c-event-page__header">
                        <div class="c-event-page__badge c-event-page__badge--standalone">
                            @datebadge(['date' => $currentOccasion->getStartDate()])
                            @enddatebadge
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @stop

    @section('content')
        @if (!empty($eventTitle))
            <h1 class="c-event-page__title">{!! $eventTitle !!}</h1>
        @endif

        @include('partials.schema.event.expired-notice', ['classes' => []])

        <div class="c-event-page__body">
            @include('partials.schema.event.description')
            @include('partials.schema.event.accessibility-features')
        </div>
    @stop

    @section('sidebar-right-content')
        @includeWhen($showPlaceCard, 'partials.schema.event.place-card')
        @include('partials.schema.event.occassions-card')
        @include('partials.schema.event.booking-link-card')
        @includeWhen(!empty($organizers), 'partials.schema.event.organizers-card')
    @stop

    @include('templates.sections.grid.content', [
        'addToDefaultClassList' => ['c-event-page__grid'],
        'addToArticleClassList' => ['c-event-page__main'],
        'addToRightSidebarClassList' => ['c-event-page__aside'],
    ])

    @includeWhen($postsListData['posts'] ?? false, 'partials.schema.event.related-posts-lidingo')
@stop
