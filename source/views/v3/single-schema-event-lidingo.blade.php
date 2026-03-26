@extends('templates.grid')

@section('layout')
    @php($eventTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))
    @php($eventImage = method_exists($post, 'getImage') ? $post->getImage() : null)
    @php($eventBadgeTimestamp = !empty($currentOccasion) ? strtotime((string) $currentOccasion->getStartDate()) : false)

    @include('partials.schema.event.expired-notice', ['classes' => []])

    <div class="c-event-page">
        {!! $hook->loopStart !!}
        {!! $hook->innerLoopStart !!}

        <article class="c-event-page__article" id="article">
            @if (!empty($eventImage))
                <div class="c-event-page__hero">
                    @image([
                        'src' => $eventImage,
                        'rounded' => false,
                        'calculateAspectRatio' => false,
                        'classList' => ['c-event-page__hero-image'],
                    ])
                    @endimage

                    @if ($eventBadgeTimestamp)
                        <div class="c-event-page__badge" aria-label="{{ __('Datum', 'lidingo-customisation') }}">
                            {{ wp_date('j F', $eventBadgeTimestamp) }}
                        </div>
                    @endif
                </div>
            @endif

            <div class="c-event-page__grid">
                <div class="c-event-page__main">
                    @if (!empty($eventTitle))
                        <h1 class="c-event-page__title">{!! $eventTitle !!}</h1>
                    @endif

                    <div class="c-event-page__body">
                        @include('partials.schema.event.description')
                        @include('partials.schema.event.accessibility-features')
                    </div>
                </div>

                <aside class="c-event-page__aside u-print-display--none">
                    @include('partials.schema.event.place-card')
                    @include('partials.schema.event.occassions-card')
                    @include('partials.schema.event.booking-link-card')
                    @includeWhen(!empty($organizers), 'partials.schema.event.organizers-card')
                </aside>
            </div>
        </article>

        {!! $hook->innerLoopEnd !!}
        {!! $hook->loopEnd !!}
    </div>

    @includeWhen($postsListData['posts'] ?? false, 'partials.schema.event.related-posts')
@stop
