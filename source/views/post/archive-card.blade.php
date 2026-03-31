@php($archiveBadgeLabel = !empty($getArchiveCardBadgeLabel) ? $getArchiveCardBadgeLabel($post) : '')
@php($archiveCardMeta = !empty($getArchiveCardMeta) ? $getArchiveCardMeta($post) : '')
@php($archiveCardMetaIcon = !empty($archiveLayoutCardMetaIcon) ? $archiveLayoutCardMetaIcon : '')
@php($archiveBadgeClassList = ['c-post-type-archive-card__badge'])
@if (!empty($archiveLayoutUsesDateBadge))
    @php($archiveBadgeClassList[] = 'c-post-type-archive-card__badge--date')
@endif

<div class="c-post-type-archive-card-shell">
    @if (!empty($archiveBadgeLabel))
        <div @class($archiveBadgeClassList) aria-hidden="true">
            <span class="c-post-type-archive-card__badge-label">{{ $archiveBadgeLabel }}</span>
        </div>
    @endif

    @card([
        'link' => $post->getPermalink(),
        'image' => $post->getImage(),
        'heading' => $post->getTitle(),
        'content' => $getExcerpt($post, 20),
        'classList' => ['u-height--100', 'c-post-type-archive-card'],
        'context' => ['archive', 'archive.list', 'archive.list.card', 'post-type-archive'],
        'containerAware' => true,
        'hasPlaceholder' => $shouldDisplayPlaceholderImage($post),
        'attributeList' => ['data-js-posts-list-item' => true],
    ])
        @slot('aboveContent')
            @if (!empty($archiveBadgeLabel))
                <p class="c-post-type-archive-card__badge-text">
                    {{ __('Märkning', 'lidingo-customisation') }}: {{ $archiveBadgeLabel }}
                </p>
            @endif

            @if (!empty($archiveCardMeta))
                <div class="c-post-type-archive-card__meta">
                    @if (!empty($archiveCardMetaIcon))
                        <span class="c-post-type-archive-card__meta-icon" aria-hidden="true">{{ $archiveCardMetaIcon }}</span>
                    @endif
                    <span class="c-post-type-archive-card__meta-text">{{ $archiveCardMeta }}</span>
                </div>
            @endif
        @endslot
    @endcard
</div>
