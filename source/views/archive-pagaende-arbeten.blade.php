@extends('templates.archive')

@section('helper-navigation')
@stop

@section('content')
    @php($hasHeroCopy = !empty($ongoingWorkArchiveTitle) || !empty($ongoingWorkArchiveLead) || !empty($ongoingWorkArchiveContent))
    @php($hasHeroMedia = !empty($ongoingWorkArchiveImageHtml))
    @php($hasFilters = $filterConfig->isTextSearchEnabled() || !empty($getTaxonomyFilterSelectComponentArguments()) || !empty($ongoingWorkYearOptions))

    <div class="c-ongoing-work-archive">
        @if ($hasHeroCopy || $hasHeroMedia)
            <article class="c-ongoing-work-archive__hero">
                <div class="c-ongoing-work-archive__helper u-print-display--none">
                    @includeIf('partials.navigation.breadcrumb')
                </div>

                <div class="c-ongoing-work-archive__hero-grid">
                    @if ($hasHeroCopy)
                        <div class="c-ongoing-work-archive__hero-content">
                            @if (!empty($ongoingWorkArchiveTitle))
                                <h1 class="c-ongoing-work-archive__title" id="page-title">
                                    {{ $ongoingWorkArchiveTitle }}
                                </h1>
                            @endif

                            @if (!empty($ongoingWorkArchiveLead))
                                <div class="c-ongoing-work-archive__lead lead">
                                    {!! $ongoingWorkArchiveLead !!}
                                </div>
                            @endif

                            @if (!empty($ongoingWorkArchiveContent))
                                <div class="c-ongoing-work-archive__content">
                                    {!! $ongoingWorkArchiveContent !!}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($hasHeroMedia)
                        <div class="c-ongoing-work-archive__hero-media">
                            {!! $ongoingWorkArchiveImageHtml !!}
                        </div>
                    @endif
                </div>
            </article>
        @endif

        @includeIf('partials.sidebar', ['id' => 'content-area-top', 'classes' => ['o-grid']])

        @element([
            'classList' => array_merge($getParentColumnClasses(), ['c-ongoing-work-archive__listing']),
            'id' => $id,
            'attributeList' => [
                'style' => 'scroll-margin-top: 100px;',
            ]
        ])
            @if ($hasFilters)
                <section class="c-ongoing-work-archive__filter-shell">
                    <h2 class="c-ongoing-work-archive__filter-title">
                        {{ __('Filtrera', 'lidingo-customisation') }}
                    </h2>
                    <style>
                        html {
                            scroll-behavior: unset !important;
                        }
                    </style>

                    <div class="s-archive-filter">
                        @form([
                            'validation' => false,
                            'method' => 'GET',
                            'action' => '?q=form_component' . ($filterConfig->getAnchor() ? '#' . $filterConfig->getAnchor() . '_id' : '')
                        ])

                        @if ($filterConfig->isTextSearchEnabled())
                            <div class="o-grid">
                                <div class="o-grid-12">
                                    @field([
                                        ...$getTextSearchFieldArguments(),
                                        'classList' => ['u-width--100'],
                                    ])
                                    @endfield
                                </div>
                            </div>
                        @endif

                        <div class="o-grid u-align-content--end">

                          @if (!empty($ongoingWorkYearOptions))
                              <div class="o-grid-12@xs o-grid-6@sm o-grid-auto@md u-level-4">
                                  @select([
                                      'label' => __('År', 'lidingo-customisation'),
                                      'name' => $ongoingWorkYearParameterName,
                                      'required' => false,
                                      'placeholder' => __('År', 'lidingo-customisation'),
                                      'options' => $ongoingWorkYearOptions,
                                      'preselected' => !empty($ongoingWorkSelectedYear) ? [(string) $ongoingWorkSelectedYear] : [],
                                      'size' => 'md',
                                  ])
                                  @endselect
                              </div>
                          @endif

                            @foreach ($getTaxonomyFilterSelectComponentArguments() as $selectArguments)
                                <div class="o-grid-12@xs o-grid-6@sm o-grid-auto@md u-level-4">
                                    @select([...$selectArguments, 'size' => 'md'])@endselect
                                </div>
                            @endforeach

                            <div class="o-grid-fit@xs o-grid-fit@sm o-grid-fit@md u-margin__top--auto">
                                @button([
                                    ...$getFilterFormSubmitButtonArguments(),
                                    'text' => __('Filtrera', 'lidingo-customisation'),
                                    'icon' => ':förbjudet:',
                                    'color' => 'primary',
                                    'classList' => ['u-display--block@xs', 'u-width--100@xs'],
                                ])
                                @endbutton
                            </div>

                            @if (!empty($ongoingWorkArchiveHasActiveFilters))
                                <div class="o-grid-fit@xs o-grid-fit@sm o-grid-fit@md u-margin__top--auto">
                                    @button([
                                        ...$getFilterFormResetButtonArguments(),
                                        'text' => __('Återställ filter', 'lidingo-customisation'),
                                        'style' => 'outlined',
                                        'color' => 'primary',
                                        'href' => $ongoingWorkArchiveResetUrl,
                                        'classList' => ['u-display--block@xs', 'u-width--100@xs'],
                                    ])
                                    @endbutton
                                </div>
                            @endif
                        </div>

                        @endform
                    </div>
                </section>
            @endif

            @element([
                'classList' => [
                    'c-ongoing-work-archive__posts',
                    'js-async-posts',
                    'o-layout-grid',
                    'o-layout-grid--cols-12',
                    'o-layout-grid--col-span-12',
                    'o-layout-grid--gap-6',
                ]
            ])
                @if (empty($posts))
                    @element(['classList' => ['o-layout-grid--col-span-12']])
                        @notice([
                            'type' => 'info',
                            'message' => [
                                'text' => $lang->noResult ?? 'No results found',
                                'size' => 'md'
                            ]
                        ])
                        @endnotice
                    @endelement
                @else
                    @if ($appearanceConfig->getDesign() === \Municipio\PostsList\Config\AppearanceConfig\PostDesign::TABLE)
                        @element(['classList' => ['o-layout-grid--col-span-12']])
                            @include('parts.table')
                        @endelement
                    @else
                        @foreach ($posts as $post)
                            @element(['classList' => $getPostColumnClasses()])
                                @includeFirst(['post.pagaende-arbeten-card', 'post.' . $appearanceConfig->getDesign()->value, 'post.card'])
                            @endelement
                        @endforeach
                    @endif
                @endif

                @if ($paginationEnabled() && !empty($getPaginationComponentArguments()))
                    @element(['classList' => ['o-layout-grid--col-span-12']])
                        @include('parts.pagination')
                    @endelement
                @endif
            @endelement
        @endelement

        @includeIf('partials.sidebar', ['id' => 'content-area', 'classes' => ['o-grid']])
    </div>
@stop
