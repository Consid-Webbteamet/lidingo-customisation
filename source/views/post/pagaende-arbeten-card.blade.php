@php($ongoingWorkDate = !empty($getOngoingWorkDateRange) ? $getOngoingWorkDateRange($post) : '')
@php($ongoingWorkStatusTerms = $post->getTerms(['status']))
@php($ongoingWorkStatusLabel = !empty($ongoingWorkStatusTerms[0]) ? $ongoingWorkStatusTerms[0]->name : '')

<div class="c-ongoing-work-card-shell">
    @if (!empty($ongoingWorkStatusLabel))
        <div class="c-ongoing-work-card__status">
            <span class="c-ongoing-work-card__status-label">{{ $ongoingWorkStatusLabel }}</span>
        </div>
    @endif

    @card([
        'link' => $post->getPermalink(),
        'image' => $post->getImage(),
        'heading' => $post->getTitle(),
        'content' => $getExcerpt($post, 20),
        'classList' => ['u-height--100', 'c-ongoing-work-card'],
        'context' => ['archive', 'archive.list', 'archive.list.card', 'ongoing-work'],
        'containerAware' => true,
        'hasPlaceholder' => $shouldDisplayPlaceholderImage($post),
        'attributeList' => ['data-js-posts-list-item' => true],
    ])
        @slot('aboveContent')
            @if (!empty($ongoingWorkDate))
                <div class="c-ongoing-work-card__date">
                    <span class="c-ongoing-work-card__date-icon" aria-hidden="true">:kalender:</span>
                    <span class="c-ongoing-work-card__date-text">{{ $ongoingWorkDate }}</span>
                </div>
            @endif
        @endslot
    @endcard
</div>
