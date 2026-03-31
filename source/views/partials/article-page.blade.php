<div class="o-container c-article-page">
    <div class="c-article-page__main">
        @includeIf('partials.sidebar', ['id' => 'content-area-top', 'classes' => ['o-grid']])

        <div class="c-article-page__inner">
            <div class="c-article-page__helper u-print-display--none">
                @includeIf('partials.navigation.breadcrumb')

                <div
                    class="c-article-page__actions"
                    role="group"
                    aria-label="{{ __('Sidfunktioner', 'lidingo-customisation') }}"
                >
                    <button
                        type="button"
                        class="button button--ghost c-content-page__print-button c-article-page__print-button"
                        onclick="window.print()"
                        aria-label="{{ __('Skriv ut', 'lidingo-customisation') }}"
                    >
                        <span class="c-button__label">
                            <span class="c-button__label-text">{{ __('Skriv ut', 'lidingo-customisation') }}</span>
                        </span>
                    </button>
                </div>
            </div>

            {!! $hook->loopStart !!}
            {!! $hook->innerLoopStart !!}

            @php($articleTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))
            @php($articleContent = $hasBlocks && !empty($post->postContentFiltered ?? null) ? $post->postContentFiltered : (is_object($post) && method_exists($post, 'getContent') ? $post->getContent() : ($post->post_content ?? '')))

            <article class="c-article-page__article" id="article">
                <div class="c-article-page__text">
                    @if (!empty($articleTitle))
                        <h1 class="c-article-page__title" id="page-title">{!! $articleTitle !!}</h1>
                    @endif

                    @if (!empty($articlePagePublishedDate))
                        <p class="c-article-page__published">
                            {{ __('Publiceringsdatum', 'lidingo-customisation') }}: {{ $articlePagePublishedDate }}
                        </p>
                    @endif

                    @if (!empty($articlePagePreamble))
                        <div class="c-article-page__preamble">
                            {!! $articlePagePreamble !!}
                        </div>
                    @endif
                </div>

                @if ($displayFeaturedImage && method_exists($post, 'getImage') && !empty($post->getImage()))
                    <div class="c-article-page__featured-image">
                        @image([
                            'src' => $post->getImage(),
                            'caption' => $featuredImage['caption'] ?? '',
                            'removeCaption' => !$displayFeaturedImageCaption,
                            'classList' => ['c-article-page__image'],
                        ])
                        @endimage
                    </div>
                @endif

                <div class="c-article-page__text">
                    {!! $hook->articleContentBefore !!}

                    @if ($postAgeNotice)
                        @notice([
                            'message' => [
                                'text' => $postAgeNotice
                            ],
                            'type' => 'info',
                            'icon' => [
                                'name' => 'lock_clock',
                                'size' => 'md',
                                'color' => 'white'
                            ]
                        ])
                        @endnotice
                    @endif

                    <div class="c-article-page__content">
                        {!! $articleContent !!}
                    </div>

                    {!! $hook->articleContentAfter !!}

                    @includeIf('partials.sidebar', ['id' => 'content-area-bottom', 'classes' => ['o-grid']])
                </div>
            </article>

            {!! $hook->innerLoopEnd !!}
            {!! $hook->loopEnd !!}
        </div>

        <div class="c-article-page__below">
            @includeIf('partials.sidebar', ['id' => 'content-area', 'classes' => ['o-grid']])
            @includeIf('partials.comments')
        </div>
    </div>
</div>
