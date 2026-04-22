@extends('templates.master')

@section('layout')
    @php($pageTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))
    @php($filterConfig = $filterConfig ?? null)
    @php($taxonomyFilterSelectArguments = !empty($getTaxonomyFilterSelectComponentArguments) ? $getTaxonomyFilterSelectComponentArguments() : [])
    @php($hasFilters = is_object($filterConfig) && ($filterConfig->isTextSearchEnabled() || !empty($taxonomyFilterSelectArguments)))

    <div class="o-container c-content-page c-post-type-archive c-page-filter">
        <div class="c-content-page__helper c-page-filter__helper u-print-display--none">
            @includeIf('partials.navigation.breadcrumb')

            <div class="c-content-page__print">
                <button
                    type="button"
                    class="button button--ghost c-content-page__print-button"
                    onclick="window.print()"
                    aria-label="{{ __('Skriv ut', 'lidingo-customisation') }}"
                >
                    <span class="c-button__label">
                        <span class="c-button__label-text">{{ __('Skriv ut', 'lidingo-customisation') }}</span>
                    </span>
                </button>
            </div>
        </div>

        {!! $hook->innerLoopStart !!}

        <div class="c-page-filter__intro">
            @if (!empty($pageTitle))
                <h1 class="c-content-page__title">{!! $pageTitle !!}</h1>
            @endif

            @if (!empty($pageFilterPreamble))
                <div class="c-content-page__preamble lead">
                    {!! $pageFilterPreamble !!}
                </div>
            @endif

            <div class="c-content-page__content">
                @if ($hasBlocks && $post)
                    {!! $post->postContentFiltered !!}
                @endif
            </div>
        </div>

        {!! $hook->innerLoopEnd !!}

        @includeIf('partials.sidebar', ['id' => 'content-area-top', 'classes' => ['o-grid']])

        @element([
            'classList' => ['c-post-type-archive__listing', 'c-page-filter__listing'],
            'id' => 'page-filter-listing',
            'attributeList' => [
                'style' => 'scroll-margin-top: 100px;',
            ]
        ])
            @include('partials.archive-post-type.types.default')
        @endelement
    </div>
@stop
