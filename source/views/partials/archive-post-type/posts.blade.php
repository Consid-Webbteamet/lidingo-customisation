@element([
    'classList' => [
        'c-post-type-archive__posts',
        'js-async-posts',
        'o-layout-grid',
        'o-layout-grid--cols-12',
        'o-layout-grid--col-span-12',
        'o-layout-grid--gap-6',
    ]
])
    @if (empty($posts))
        @element(['classList' => ['o-layout-grid--col-span-12']])
            @notice([
                'type' => 'info',
                'message' => [
                    'text' => $lang->noResult ?? 'No results found',
                    'size' => 'md'
                ]
            ])
            @endnotice
        @endelement
    @else
        @if ($appearanceConfig->getDesign() === \Municipio\PostsList\Config\AppearanceConfig\PostDesign::TABLE)
            @element(['classList' => ['o-layout-grid--col-span-12']])
                @include('partials.archive-post-type.table')
            @endelement
        @else
            @foreach ($posts as $post)
                @element(['classList' => $getPostColumnClasses()])
                    @if ($appearanceConfig->getDesign() === \Municipio\PostsList\Config\AppearanceConfig\PostDesign::SCHEMA)
                        @includeFirst(['post.' . $appearanceConfig->getDesign()->value, 'post.archive-card', 'post.card'])
                    @else
                        @includeFirst(['post.archive-card', 'post.' . $appearanceConfig->getDesign()->value, 'post.card'])
                    @endif
                @endelement
            @endforeach
        @endif
    @endif

    @if ($paginationEnabled() && !empty($getPaginationComponentArguments()))
        @element(['classList' => ['o-layout-grid--col-span-12']])
            @include('parts.pagination')
        @endelement
    @endif
@endelement
