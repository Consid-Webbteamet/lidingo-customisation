@if (!empty($serviceInfoArchiveEnabled))
    @include('partials.archive-post-type.service-info-archive')
@else
    @include('partials.archive-post-type.types.default')
@endif
