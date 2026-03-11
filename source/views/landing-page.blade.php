@extends('templates.master')

@section('hero-top-sidebar')
    @includeIf('partials.hero')
    @includeWhen($quicklinksPlacement !== 'below_content', 'partials.navigation.fixed')
    @includeIf('partials.sidebar', ['id' => 'top-sidebar'])
@stop

@section('layout')
    <div class="o-container u-display--flex u-flex-direction--column">
        {!! $hook->innerLoopStart !!}

        @if ($hasBlocks && $post)
            {!! $post->postContentFiltered !!}
        @endif

        {!! $hook->innerLoopEnd !!}

        @includeIf('partials.sidebar', ['id' => 'content-area-top', 'classes' => ['o-grid']])
        @includeIf('partials.sidebar', ['id' => 'content-area', 'classes' => ['o-grid']])
        @includeIf('partials.sidebar', ['id' => 'content-area-bottom', 'classes' => ['o-grid']])
    </div>

    @includeWhen($quicklinksPlacement === 'below_content', 'partials.navigation.fixed')
@stop
