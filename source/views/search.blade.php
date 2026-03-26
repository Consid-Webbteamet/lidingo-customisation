@extends('templates.master')

@section('layout')
    <div class="c-search-results">
        <section class="o-container c-search-results__intro">
            <div class="o-grid">
                <div class="o-grid-12">
                    <div class="c-search-results__helper u-print-display--none">
                        @includeIf('partials.navigation.breadcrumb')
                    </div>

                    <h1 class="c-search-results__title">{{ $lang->searchResults }}</h1>

                    @if ($hook->customSearchPage)
                        <div class="c-search-results__custom-page">
                            {!! $hook->customSearchPage !!}
                        </div>
                    @else
                        @form([
                            'method' => 'get',
                            'action' => $homeUrl,
                            'classList' => ['c-search-results__form']
                        ])
                            @if (!empty($searchPageActiveType))
                                <input type="hidden" name="type" value="{{ $searchPageActiveType }}">
                            @endif

                            @group([
                                'direction' => 'horizontal',
                                'classList' => ['c-search-results__form-group']
                            ])
                                @field([
                                    'id' => 'search-results-query',
                                    'type' => 'search',
                                    'name' => 's',
                                    'placeholder' => $lang->searchOn . ' ' . $siteName,
                                    'value' => $searchPageKeyword,
                                    'classList' => ['u-flex-grow--1', 'c-search-results__field'],
                                    'label' => $lang->search,
                                    'size' => 'lg',
                                    'radius' => 'xs',
                                    'icon' => ['icon' => 'search']
                                ])
                                @endfield

                                @button([
                                    'text' => $lang->search,
                                    'color' => 'primary',
                                    'type' => 'filled',
                                    'size' => 'lg',
                                    'icon' => ':sök:',
                                    'classList' => ['c-search-results__submit'],
                                    'attributeList' => [
                                        'id' => 'search-results-submit'
                                    ]
                                ])
                                @endbutton
                            @endgroup
                        @endform

                        <p class="c-search-results__count">
                            @if (!empty($searchPageHighlightedKeyword))
                                {{ $resultCount }} {{ __('träffar för', 'lidingo-customisation') }}&nbsp;{!! $searchPageHighlightedKeyword !!}
                            @else
                                {{ $lang->found }} {{ $resultCount }}
                            @endif
                        </p>

                        @if (!empty($searchPageTypeButtons))
                            <nav class="c-search-results__filters" aria-label="{{ __('Filtrera sökresultat', 'lidingo-customisation') }}">
                                @foreach ($searchPageTypeButtons as $button)
                                    <a
                                        class="c-search-results__filter button {{ $button['isActive'] ? 'button--primary' : 'button--ghost' }}"
                                        href="{{ $button['url'] }}"
                                        aria-current="{{ $button['isActive'] ? 'page' : 'false' }}"
                                    >
                                        {{ $button['label'] }} ({{ $button['count'] }})
                                    </a>
                                @endforeach
                            </nav>
                        @endif
                    @endif
                </div>
            </div>
        </section>

        {!! $hook->searchNotices !!}

        @if (!$resultCount)
            <section class="o-container c-search-results__empty">
                <div class="o-grid">
                    <div class="o-grid-12">
                        @notice([
                            'type' => 'info',
                            'message' => [
                                'text' => $lang->noResult,
                                'size' => 'md'
                            ]
                        ])
                        @endnotice
                    </div>
                </div>
            </section>
        @elseif (!$hook->customSearchPage)
            <section class="o-container c-search-results__listing">
                <div class="o-grid">
                    <div class="o-grid-12">
                        {!! $hook->loopStart !!}

                        <div class="c-search-results__cards">
                            @foreach ($searchPageResults as $result)
                                <a class="c-search-results__card" href="{{ esc_url($result['permalink']) }}">
                                    <div class="c-search-results__card-main">
                                        <div class="c-search-results__card-copy">
                                            <h2 class="c-search-results__card-title">{!! $result['title'] !!}</h2>

                                            @if (!empty($result['excerpt']))
                                                <p class="c-search-results__card-excerpt">{!! $result['excerpt'] !!}</p>
                                            @endif

                                            @if (!empty($result['breadcrumbs']))
                                                <p class="c-search-results__card-breadcrumbs">
                                                    {{ implode(' · ', $result['breadcrumbs']) }}
                                                </p>
                                            @endif
                                        </div>

                                        @if (!empty($result['image']))
                                            <div class="c-search-results__card-media">
                                                <img
                                                    class="c-search-results__card-image"
                                                    src="{{ $result['image']['url'] }}"
                                                    alt="{{ $result['image']['alt'] }}"
                                                    loading="lazy"
                                                    width="620"
                                                    height="300"
                                                >
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        {!! $hook->loopEnd !!}
                    </div>
                </div>
            </section>

            @if ($showPagination)
                <section class="c-pagination-block">
                    <nav class="c-pagination-block__nav" aria-label="{{ __('Sidnavigering för sökresultat', 'lidingo-customisation') }}">
                        <ul class="c-pagination-block__list">
                            <li class="c-pagination-block__item">
                                @if ($searchPagePaginationHasPrevious)
                                    @button([
                                        'href' => $searchPagePaginationPreviousUrl,
                                        'icon' => 'chevron_left',
                                        'text' => __('Föregående sida', 'lidingo-customisation'),
                                        'size' => 'sm',
                                        'color' => 'default',
                                        'classList' => ['c-pagination-block__control', 'c-pagination-block__control--previous'],
                                        'attributeList' => [
                                            'aria-label' => __('Föregående sida', 'lidingo-customisation'),
                                        ],
                                    ])
                                    @endbutton
                                @else
                                    @button([
                                        'icon' => ':förbjudet:',
                                        'text' => __('Ingen föregående sida', 'lidingo-customisation'),
                                        'size' => 'sm',
                                        'color' => 'default',
                                        'classList' => ['c-pagination-block__control', 'c-pagination-block__control--previous', 'c-pagination-block__control--disabled'],
                                        'attributeList' => [
                                            'disabled' => 'true',
                                            'aria-disabled' => 'true',
                                            'aria-label' => __('Ingen föregående sida', 'lidingo-customisation'),
                                        ],
                                    ])
                                    @endbutton
                                @endif
                            </li>

                            @foreach ($paginationList as $paginationItem)
                                <li class="c-pagination-block__item">
                                    <a
                                        class="c-pagination-block__link {{ (int) $paginationItem['label'] === (int) $currentPagePagination ? 'c-pagination-block__link--active' : '' }}"
                                        href="{{ $paginationItem['href'] }}"
                                        aria-current="{{ (int) $paginationItem['label'] === (int) $currentPagePagination ? 'page' : 'false' }}"
                                    >
                                        {{ $paginationItem['label'] }}
                                    </a>
                                </li>
                            @endforeach

                            <li class="c-pagination-block__item">
                                @if ($searchPagePaginationHasNext)
                                    @button([
                                        'href' => $searchPagePaginationNextUrl,
                                        'icon' => 'chevron_right',
                                        'text' => __('Nästa sida', 'lidingo-customisation'),
                                        'size' => 'sm',
                                        'color' => 'default',
                                        'classList' => ['c-pagination-block__control', 'c-pagination-block__control--next'],
                                        'attributeList' => [
                                            'aria-label' => __('Nästa sida', 'lidingo-customisation'),
                                        ],
                                    ])
                                    @endbutton
                                @else
                                    @button([
                                        'icon' => ':förbjudet:',
                                        'text' => __('Ingen nästa sida', 'lidingo-customisation'),
                                        'size' => 'sm',
                                        'color' => 'default',
                                        'classList' => ['c-pagination-block__control', 'c-pagination-block__control--next', 'c-pagination-block__control--disabled'],
                                        'attributeList' => [
                                            'disabled' => 'true',
                                            'aria-disabled' => 'true',
                                            'aria-label' => __('Ingen nästa sida', 'lidingo-customisation'),
                                        ],
                                    ])
                                    @endbutton
                                @endif
                            </li>
                        </ul>
                    </nav>
                </section>
            @endif
        @endif
    </div>
@stop
