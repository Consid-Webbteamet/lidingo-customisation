@extends('templates.archive')

@section('helper-navigation')
@stop

@section('content')
    @php($hasHeroCopy = !empty($archiveLayoutTitle) || !empty($archiveLayoutLead) || !empty($archiveLayoutContent))
    @php($hasHeroMedia = !empty($archiveLayoutImageHtml))
    @php($yearOptions = is_array($archiveLayoutYearOptions ?? null) ? $archiveLayoutYearOptions : [])
    @php($filterConfig = $filterConfig ?? null)
    @php($taxonomyFilterSelectArguments = !empty($getTaxonomyFilterSelectComponentArguments) ? $getTaxonomyFilterSelectComponentArguments() : [])
    @php($parentColumnClasses = !empty($getParentColumnClasses) ? $getParentColumnClasses() : ['o-grid-12'])
    @php($archiveElementId = !empty($id) ? $id : 'archive-post-type')
    @php($hasFilters = is_object($filterConfig) && (($filterConfig->isTextSearchEnabled() || $filterConfig->isDateFilterEnabled() || !empty($taxonomyFilterSelectArguments) || !empty($yearOptions))))

    <div class="c-post-type-archive">
        @if ($hasHeroCopy || $hasHeroMedia)
            <header class="c-post-type-archive__hero">
                <div class="c-post-type-archive__helper u-print-display--none">
                    @includeIf('partials.navigation.breadcrumb')
                </div>

                <div class="c-post-type-archive__hero-grid">
                    @if ($hasHeroCopy)
                        <div class="c-post-type-archive__hero-content">
                            @if (!empty($archiveLayoutTitle))
                                <h1 class="c-post-type-archive__title" id="page-title">
                                    {{ $archiveLayoutTitle }}
                                </h1>
                            @endif

                            @if (!empty($archiveLayoutLead))
                                <div class="c-post-type-archive__lead lead">
                                    {!! $archiveLayoutLead !!}
                                </div>
                            @endif

                            @if (!empty($archiveLayoutContent))
                                <div class="c-post-type-archive__content">
                                    {!! $archiveLayoutContent !!}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($hasHeroMedia)
                        <div class="c-post-type-archive__hero-media">
                            {!! $archiveLayoutImageHtml !!}
                        </div>
                    @endif
                </div>
            </header>
        @endif

        @includeIf('partials.sidebar', ['id' => 'content-area-top', 'classes' => ['o-grid']])

        @element([
            'classList' => array_merge($parentColumnClasses, ['c-post-type-archive__listing']),
            'id' => $archiveElementId,
            'attributeList' => [
                'style' => 'scroll-margin-top: 100px;',
            ]
        ])
            @if (!empty($serviceInfoArchiveEnabled))
                <div class="c-service-info-archive">
                    @foreach ($serviceInfoArchiveSections as $section)
                        <section class="c-service-info-archive__section">
                            <h2 class="c-service-info-archive__heading">{{ $section['title'] }}</h2>

                            @if (empty($section['items']))
                                <div class="c-service-info-archive__empty">
                                    {{ $section['emptyText'] }}
                                </div>
                            @else
                                <div class="c-service-info-archive__items">
                                    @foreach ($section['items'] as $item)
                                        <article class="c-service-info-archive__card">
                                            <a href="{{ $item['link'] }}" class="c-service-info-archive__card-link">
                                                @if (!empty($item['icon']))
                                                    <div class="c-service-info-archive__icon-shell" aria-hidden="true">
                                                        <span class="c-service-info-archive__icon">
                                                            @icon(['icon' => $item['icon']])
                                                            @endicon
                                                        </span>
                                                    </div>
                                                @endif

                                                <div class="c-service-info-archive__card-content">
                                                    @if (!empty($item['formattedDate']))
                                                        <p class="c-service-info-archive__date">{!! $item['formattedDate'] !!}</p>
                                                    @endif

                                                    <h3 class="c-service-info-archive__card-title">{{ $item['title'] }}</h3>
                                                </div>
                                            </a>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endforeach

                    @if (!empty($serviceInfoArchiveExternalItems))
                        <section class="c-service-info-archive__section c-service-info-archive__section--external">
                            <h2 class="c-service-info-archive__heading">
                                {{ $serviceInfoArchiveExternalSectionTitle ?? __('Driftstörningar i system som sköts av andra aktörer', 'lidingo-customisation') }}
                            </h2>

                            <div class="c-service-info-archive__external-grid">
                                @foreach ($serviceInfoArchiveExternalItems as $item)
                                    <article class="c-service-info-archive__card c-service-info-archive__card--external">
                                        <a
                                            href="{{ $item['link'] }}"
                                            class="c-service-info-archive__card-link"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            @if (!empty($item['icon']))
                                                <div class="c-service-info-archive__icon-shell" aria-hidden="true">
                                                    <span class="c-service-info-archive__icon">
                                                        @icon(['icon' => $item['icon']])
                                                        @endicon
                                                    </span>
                                                </div>
                                            @endif

                                            <div class="c-service-info-archive__card-content">
                                                <h3 class="c-service-info-archive__card-title">{{ $item['title'] }}</h3>
                                                <span class="sr-only">
                                                    {{ __('Öppnas i ny flik', 'lidingo-customisation') }}
                                                </span>

                                                @if (!empty($item['description']))
                                                    <div class="c-service-info-archive__card-description">
                                                        {!! wp_kses_post($item['description']) !!}
                                                    </div>
                                                @endif
                                            </div>
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            @elseif ($hasFilters)
                <section class="c-post-type-archive__filter-shell">
                    <h2 class="c-post-type-archive__filter-title">
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

                        @if ($filterConfig->isDateFilterEnabled())
                            <div class="o-grid">
                                <div class="o-grid-12@xs o-grid-6@sm">
                                    @field($getDateFilterFieldArguments()['from'])@endfield
                                </div>
                                <div class="o-grid-12@xs o-grid-6@sm">
                                    @field($getDateFilterFieldArguments()['to'])@endfield
                                </div>
                            </div>
                        @endif

                        <div class="o-grid u-align-content--end">
                            @if (!empty($yearOptions))
                                <div class="o-grid-12@xs o-grid-6@sm o-grid-auto@md u-level-4">
                                    @select([
                                        'label' => __('År', 'lidingo-customisation'),
                                        'name' => $archiveLayoutYearParameterName,
                                        'required' => false,
                                        'placeholder' => __('År', 'lidingo-customisation'),
                                        'options' => $yearOptions,
                                        'preselected' => !empty($archiveLayoutSelectedYear) ? [(string) $archiveLayoutSelectedYear] : [],
                                        'size' => 'md',
                                    ])
                                    @endselect
                                </div>
                            @endif

                            @foreach ($taxonomyFilterSelectArguments as $selectArguments)
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

                            @if (!empty($archiveLayoutHasActiveFilters))
                                <div class="o-grid-fit@xs o-grid-fit@sm o-grid-fit@md u-margin__top--auto">
                                    @button([
                                        ...$getFilterFormResetButtonArguments(),
                                        'text' => __('Återställ filter', 'lidingo-customisation'),
                                        'style' => 'outlined',
                                        'color' => 'primary',
                                        'href' => $archiveLayoutResetUrl,
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

            @unless (!empty($serviceInfoArchiveEnabled))
                @element([
                    'classList' => [
                        'c-post-type-archive__posts',
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
                                    @if ($appearanceConfig->getDesign() === \Municipio\PostsList\Config\AppearanceConfig\PostDesign::SCHEMA)
                                        @includeFirst(['post.' . $appearanceConfig->getDesign()->value, 'post.archive-card', 'post.card'])
                                    @else
                                        @includeFirst(['post.archive-card', 'post.' . $appearanceConfig->getDesign()->value, 'post.card'])
                                    @endif
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
            @endunless
        @endelement

        @includeIf('partials.sidebar', ['id' => 'content-area', 'classes' => ['o-grid']])
    </div>
@stop
