@if (!empty($serviceInfoArchiveEnabled))
    @include('partials.archive-post-type.service-info-archive')
@elseif ($hasFilters)
    @include('partials.archive-post-type.filters')
@endif

@unless (!empty($serviceInfoArchiveEnabled))
    @include('partials.archive-post-type.posts')
@endunless
