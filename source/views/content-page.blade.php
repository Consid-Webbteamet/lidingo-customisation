@extends('templates.master')

@section('layout')
    <div class="o-container c-content-page">
        <div class="c-content-page__grid">
            <div class="c-content-page__main">
                <div class="c-content-page__main-inner">
                    <div class="c-content-page__helper u-print-display--none">
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

                    @php($pageTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))

                    <div class="c-content-page__content-inner">
                        @if (!empty($pageTitle))
                            <h1 class="c-content-page__title">{!! $pageTitle !!}</h1>
                        @endif

                        @if (!empty($contentPagePreamble))
                            <div class="c-content-page__preamble lead">
                                {!! $contentPagePreamble !!}
                            </div>
                        @endif

                        <div class="c-content-page__content">
                            @if ($hasBlocks && $post)
                                {!! $post->postContentFiltered !!}
                            @endif
                        </div>

                        @if (!empty($contentPagePublishedDate))
                            <p class="c-content-page__published">
                                {{ __('Publiceringsdatum', 'lidingo-customisation') }}: {{ $contentPagePublishedDate }}
                            </p>
                        @endif

                        @includeIf('partials.sidebar', ['id' => 'content-area-bottom', 'classes' => ['o-grid']])
                    </div>

                    {!! $hook->innerLoopEnd !!}
                </div>
            </div>

            @if (is_active_sidebar('right-sidebar'))
                <aside class="c-content-page__aside">
                    <div class="c-content-page__aside-inner">
                        @includeIf('partials.sidebar', ['id' => 'right-sidebar'])
                    </div>
                </aside>
            @endif
        </div>

        <div class="c-content-page__below">
            @includeIf('partials.sidebar', ['id' => 'content-area', 'classes' => ['o-grid']])
        </div>
    </div>
@stop
