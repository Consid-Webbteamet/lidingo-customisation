@php($hasRightSidebar = is_active_sidebar('right-sidebar'))

<div class="c-service-info-archive{{ $hasRightSidebar ? ' c-service-info-archive--has-sidebar' : '' }}">
    <div class="c-service-info-archive__main">
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
                                    @if (!empty($item['iconImageHtml']))
                                        <div class="c-service-info-archive__icon-shell" aria-hidden="true">
                                            <span class="c-service-info-archive__icon">
                                                {!! $item['iconImageHtml'] !!}
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
                                @if (!empty($item['iconImageHtml']))
                                    <div class="c-service-info-archive__icon-shell" aria-hidden="true">
                                        <span class="c-service-info-archive__icon">
                                            {!! $item['iconImageHtml'] !!}
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

    @if ($hasRightSidebar)
        <aside class="c-service-info-archive__aside">
            <div class="c-service-info-archive__aside-inner">
                @includeIf('partials.sidebar', ['id' => 'right-sidebar'])
            </div>
        </aside>
    @endif
</div>
