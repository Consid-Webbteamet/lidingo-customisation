@extends('templates.master')

@section('layout')
    @php($serviceInfoPostId = (int) get_the_ID())
    @php($serviceInfoTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))
    @php($serviceInfoContent = $hasBlocks && !empty($post->postContentFiltered ?? null) ? $post->postContentFiltered : (is_object($post) && method_exists($post, 'getContent') ? $post->getContent() : ($post->post_content ?? '')))
    @php($serviceInfoTerms = $serviceInfoPostId > 0 ? get_the_terms($serviceInfoPostId, 'service_category') : null)
    @php($serviceInfoFirstTerm = is_array($serviceInfoTerms) ? reset($serviceInfoTerms) : false)
    @php($serviceInfoIcon = is_object($serviceInfoFirstTerm) ? get_field('icon', 'service_category_' . $serviceInfoFirstTerm->term_id) : '')
    @php($serviceInfoStartDate = $serviceInfoPostId > 0 ? (string) get_field('start_date', $serviceInfoPostId) : '')
    @php($serviceInfoEndDate = $serviceInfoPostId > 0 ? (string) get_field('end_date', $serviceInfoPostId) : '')
    @php($serviceInfoFormattedDate = class_exists(\ModularityServiceInfo\Helper\DateFormatter::class) ? \ModularityServiceInfo\Helper\DateFormatter::formatDateRange($serviceInfoStartDate, $serviceInfoEndDate) : trim($serviceInfoStartDate . ($serviceInfoEndDate !== '' ? ' - ' . $serviceInfoEndDate : '')))
    @php($serviceInfoFormattedDate = str_replace(['&ndash;', '&#8211;', '–'], '-', (string) $serviceInfoFormattedDate))
    @php($serviceInfoPublishedTimestamp = $serviceInfoPostId > 0 ? get_post_timestamp($serviceInfoPostId, 'date') : false)
    @php($serviceInfoPublishedDate = is_int($serviceInfoPublishedTimestamp) ? wp_date((string) get_option('date_format', 'j F Y'), $serviceInfoPublishedTimestamp) : '')

    <div class="o-container c-service-info-single">
        @includeIf('partials.sidebar', ['id' => 'content-area-top', 'classes' => ['o-grid']])

        <div class="c-service-info-single__helper u-print-display--none">
            @includeIf('partials.navigation.breadcrumb')
        </div>

        {!! $hook->loopStart !!}
        {!! $hook->innerLoopStart !!}

        <div class="c-service-info-single__grid">
            <article class="c-service-info-single__main">
                <header class="c-service-info-single__header">
                    @if (is_string($serviceInfoIcon) && $serviceInfoIcon !== '')
                        <span class="c-service-info-single__icon" aria-hidden="true">
                            @icon(['icon' => $serviceInfoIcon])
                            @endicon
                        </span>
                    @endif

                    @if (!empty($serviceInfoTitle))
                        <h1 class="c-service-info-single__title">{!! $serviceInfoTitle !!}</h1>
                    @endif
                </header>

                @if ($serviceInfoFormattedDate !== '')
                    <p class="c-service-info-single__date">{{ $serviceInfoFormattedDate }}</p>
                @endif

                @if (!empty($serviceInfoContent))
                    <div class="c-service-info-single__content">
                        {!! $serviceInfoContent !!}
                    </div>
                @endif

                @if ($serviceInfoPublishedDate !== '')
                    <p class="c-service-info-single__published">
                        {{ __('Publiceringsdatum', 'lidingo-customisation') }}: {{ $serviceInfoPublishedDate }}
                    </p>
                @endif
            </article>

            <aside class="c-service-info-single__aside" aria-label="{{ __('Högerkolumn', 'lidingo-customisation') }}">
            </aside>
        </div>

        {!! $hook->innerLoopEnd !!}
        {!! $hook->loopEnd !!}

        @includeIf('partials.sidebar', ['id' => 'content-area-bottom', 'classes' => ['o-grid']])
    </div>
@stop
