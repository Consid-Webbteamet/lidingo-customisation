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
