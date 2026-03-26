@extends('templates.grid')

@section('layout')
    @php($eventTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))
    @php($eventImage = method_exists($post, 'getImage') ? $post->getImage(2880) : null)
    @php($eventBadgeTimestamp = !empty($currentOccasion) ? strtotime((string) $currentOccasion->getStartDate()) : false)
    @php($eventCtaLabel = !empty($eventPageCtaLabel) ? $eventPageCtaLabel : __('Köp biljetter', 'lidingo-customisation'))

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

                    @if (!empty($eventPageLeadHtml))
                        <div class="c-event-page__lead">
                            {!! $eventPageLeadHtml !!}
                        </div>
                    @endif

                    @if (!empty($bookingLink) && !$eventIsInThePast)
                        <a
                            class="button button--primary button--icon-arrow c-event-page__cta"
                            href="{{ $bookingLink }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ $eventCtaLabel }}
                        </a>
                    @endif

                    <div class="c-event-page__body">
                        @if (!empty($eventPageBodyHeading))
                            <h2 class="c-event-page__section-title">{!! $eventPageBodyHeading !!}</h2>
                        @endif

                        @if (!empty($eventPageBodyHtml))
                            <div class="c-event-page__content">
                                {!! $eventPageBodyHtml !!}
                            </div>
                        @endif

                        @if (!empty($scheduleDescription))
                            <div class="c-event-page__schedule-description">
                                {!! $scheduleDescription !!}
                            </div>
                        @endif
                    </div>
                </div>

                <aside class="c-event-page__aside u-print-display--none">
                    @include('partials.schema.event.occassions-card')
                    @include('partials.schema.event.place-card')
                    @includeWhen(!empty($organizers), 'partials.schema.event.organizers-card')
                </aside>
            </div>

            @include('partials.schema.event.accessibility-features')
        </article>

        {!! $hook->innerLoopEnd !!}
        {!! $hook->loopEnd !!}
    </div>

    @includeWhen($postsListData['posts'] ?? false, 'partials.schema.event.related-posts')
@stop
