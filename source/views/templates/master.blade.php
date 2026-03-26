<!DOCTYPE html>
<html {!! $languageAttributes !!}>

@include('templates.sections.head')

{{-- Content --}}
@section('body-content')
    <div class="site-wrapper">
        {{-- Banner Notices --}}
        @include('templates.sections.banner-notices')

        {{-- Site banner --}}
        @include('templates.sections.site-banner')

        {{-- Site header --}}
        @include('templates.sections.site-header')

        {{-- Helper navigation --}}
        @include('templates.sections.helper-nav')

        @if (!empty($renderContentNoticesBeforeHero))
            {{-- Notices before hero --}}
            @include('templates.sections.content-notices')
        @endif

        {{-- Hero area and top sidebar --}}
        @hasSection('hero-top-sidebar')
            @yield('hero-top-sidebar')
        @endif

        {{-- Before page layout --}}
        @section('before-layout')
        @show

        @if (empty($renderContentNoticesBeforeHero))
            {{-- Notices before content --}}
            @include('templates.sections.content-notices')
        @endif

        {{-- Page layout --}}
        <main id="main-content" tabindex="-1">
            @include('templates.sections.master.layout')
        </main>

        {{-- After page layout --}}
        @yield('after-layout')
    </div>

    {{-- Bottom sidebar --}}
    @include('templates.sections.bottom-sidebar')

    @section('footer')
        @includeIf('partials.footer')
    @show

    {{-- Floating menu --}}
    @include('partials.navigation.floating')

    {{-- Notices Notice::add() --}}
    {{-- Shows up in the bottom left corner as toast messages --}}
    @include('templates.sections.toast-notices')

    {{-- Wordpress required call to wp_footer() --}}
    {!! $wpFooter !!}
@stop

{{-- Including body --}}
@include('templates.sections.body')

</html>
