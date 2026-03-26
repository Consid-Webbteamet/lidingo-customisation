@extends('templates.grid')

@section('layout')
    @php($eventTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))
    @php($eventImage = method_exists($post, 'getImage') ? $post->getImage() : null)

    @include('partials.schema.event.expired-notice', ['classes' => []])

    @section('above-content')
        @parent

        <div class="c-event-page">
            <div class="c-event-page__article">
                @if (!empty($eventImage))
                    <div class="c-event-page__hero">
                        @image([
                            'src' => $eventImage,
                            'rounded' => false,
                            'calculateAspectRatio' => false,
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

                @if (!empty($eventTitle) || (!empty($currentOccasion) && empty($eventImage)))
                    <div class="c-event-page__header">
                        @if (!empty($eventTitle))
                            <h1 class="c-event-page__title">{!! $eventTitle !!}</h1>
                        @endif

                        @if (!empty($currentOccasion) && empty($eventImage))
                            <div class="c-event-page__badge c-event-page__badge--standalone">
                                @datebadge(['date' => $currentOccasion->getStartDate()])
                                @enddatebadge
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @stop

    @section('content')
        <div class="c-event-page__body">
            @include('partials.schema.event.description')
            @include('partials.schema.event.accessibility-features')
        </div>
    @stop

    @section('sidebar-right-content')
        @include('partials.schema.event.place-card')
        @include('partials.schema.event.occassions-card')
        @include('partials.schema.event.booking-link-card')
        @includeWhen(!empty($organizers), 'partials.schema.event.organizers-card')
    @stop

    @include('templates.sections.grid.content', [
        'addToDefaultClassList' => ['c-event-page__grid'],
        'addToArticleClassList' => ['c-event-page__main'],
        'addToRightSidebarClassList' => ['c-event-page__aside'],
    ])

    @includeWhen($postsListData['posts'] ?? false, 'partials.schema.event.related-posts')
@stop
