@php($heroAsideCard = is_array($archiveLayoutHeroAsideCard ?? null) ? $archiveLayoutHeroAsideCard : null)
@php($hasHeroAsideCard = !empty($heroAsideCard['title']) && !empty($heroAsideCard['link']['url']) && !empty($heroAsideCard['link']['title']))

<header class="c-post-type-archive__hero">
    <div class="c-post-type-archive__helper u-print-display--none">
        @includeIf('partials.navigation.breadcrumb')
    </div>

    <div class="c-post-type-archive__hero-grid{{ $hasHeroAsideCard ? ' c-post-type-archive__hero-grid--with-card' : '' }}">
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

        @if ($hasHeroAsideCard)
            <aside class="c-post-type-archive__hero-card">
                <h2 class="c-post-type-archive__hero-card-title">{{ $heroAsideCard['title'] }}</h2>

                <a
                    href="{{ $heroAsideCard['link']['url'] }}"
                    class="c-post-type-archive__hero-card-link button button--big button--icon-arrow"
                    target="{{ $heroAsideCard['link']['target'] }}"
                    @if (!empty($heroAsideCard['link']['rel']))
                        rel="{{ $heroAsideCard['link']['rel'] }}"
                    @endif
                >
                    {{ $heroAsideCard['link']['title'] }}
                </a>
            </aside>
        @elseif ($hasHeroMedia)
            <div class="c-post-type-archive__hero-media">
                {!! $archiveLayoutImageHtml !!}
            </div>
        @endif
    </div>
</header>
