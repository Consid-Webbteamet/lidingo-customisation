@extends('templates.archive')

@section('helper-navigation')
@stop

@section('content.below')
@stop

@section('content')
    @php($hasHeroCopy = !empty($archiveLayoutTitle) || !empty($archiveLayoutLead) || !empty($archiveLayoutContent))
    @php($hasHeroMedia = !empty($archiveLayoutImageHtml))
    @php($yearOptions = is_array($archiveLayoutYearOptions ?? null) ? $archiveLayoutYearOptions : [])
    @php($filterConfig = $filterConfig ?? null)
    @php($taxonomyFilterSelectArguments = !empty($getTaxonomyFilterSelectComponentArguments) ? $getTaxonomyFilterSelectComponentArguments() : [])
    @php($parentColumnClasses = !empty($getParentColumnClasses) ? $getParentColumnClasses() : ['o-grid-12'])
    @php($archiveElementId = !empty($id) ? $id : 'archive-post-type')
    @php($archiveLayoutPostTypeName = !empty($archiveLayoutPostType) && is_string($archiveLayoutPostType) ? sanitize_key($archiveLayoutPostType) : '')
    @php($archiveTypeTemplates = !empty($archiveLayoutPostTypeName) ? ['partials.archive-post-type.types.' . $archiveLayoutPostTypeName, 'partials.archive-post-type.types.default'] : ['partials.archive-post-type.types.default'])
    @php($hasFilters = is_object($filterConfig) && (($filterConfig->isTextSearchEnabled() || $filterConfig->isDateFilterEnabled() || !empty($taxonomyFilterSelectArguments) || !empty($yearOptions))))

    <div class="c-post-type-archive">
        @if ($hasHeroCopy || $hasHeroMedia)
            @include('partials.archive-post-type.hero')
        @endif

        @includeIf('partials.sidebar', ['id' => 'content-area-top', 'classes' => ['o-grid']])

        @element([
            'classList' => array_merge($parentColumnClasses, ['c-post-type-archive__listing']),
            'id' => $archiveElementId,
            'attributeList' => [
                'style' => 'scroll-margin-top: 100px;',
            ]
        ])
            @includeFirst($archiveTypeTemplates)
        @endelement

    </div>
@stop
