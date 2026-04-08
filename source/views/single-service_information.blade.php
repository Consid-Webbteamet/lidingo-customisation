@extends('templates.master')

@section('layout')
    @php($serviceInfoPostId = (int) get_the_ID())
    @php($serviceInfoTitle = method_exists($post, 'getTitle') ? $post->getTitle() : ($post->post_title ?? ''))
    @php($serviceInfoContent = $hasBlocks && !empty($post->postContentFiltered ?? null) ? $post->postContentFiltered : (is_object($post) && method_exists($post, 'getContent') ? $post->getContent() : ($post->post_content ?? '')))
    @php($serviceInfoTerms = $serviceInfoPostId > 0 ? get_the_terms($serviceInfoPostId, 'service_category') : null)
    @php($serviceInfoFirstTerm = is_array($serviceInfoTerms) ? reset($serviceInfoTerms) : false)
    @php($serviceInfoIconAttachmentId = is_object($serviceInfoFirstTerm) ? (int) get_field('icon', 'service_category_' . $serviceInfoFirstTerm->term_id) : 0)
    @php($serviceInfoIconImageHtml = $serviceInfoIconAttachmentId > 0 ? wp_get_attachment_image($serviceInfoIconAttachmentId, 'thumbnail', false, [
        'class' => 'c-service-info-single__icon-image',
        'alt' => '',
        'loading' => 'lazy',
        'decoding' => 'async',
    ]) : '')
    @php($serviceInfoStartDate = $serviceInfoPostId > 0 ? (string) get_field('start_date', $serviceInfoPostId) : '')
    @php($serviceInfoEndDate = $serviceInfoPostId > 0 ? (string) get_field('end_date', $serviceInfoPostId) : '')
    @php($serviceInfoFormattedDate = class_exists(\ModularityServiceInfo\Helper\DateFormatter::class) ? \ModularityServiceInfo\Helper\DateFormatter::formatDateRange($serviceInfoStartDate, $serviceInfoEndDate) : trim($serviceInfoStartDate . ($serviceInfoEndDate !== '' ? ' - ' . $serviceInfoEndDate : '')))
    @php($serviceInfoFormattedDate = str_replace(['&ndash;', '&#8211;', '–'], '-', (string) $serviceInfoFormattedDate))
    @php($serviceInfoPublishedTimestamp = $serviceInfoPostId > 0 ? get_post_timestamp($serviceInfoPostId, 'date') : false)
    @php($serviceInfoPublishedDate = is_int($serviceInfoPublishedTimestamp) ? wp_date((string) get_option('date_format', 'j F Y'), $serviceInfoPublishedTimestamp) : '')
    @php($serviceInfoSidebarHeading = trim((string) get_field(\LidingoCustomisation\AcfFields\ServiceInfoSingleSidebarFields::FIELD_NAME_HEADING, $serviceInfoPostId)))
    @php($serviceInfoSidebarSubheading = trim((string) get_field(\LidingoCustomisation\AcfFields\ServiceInfoSingleSidebarFields::FIELD_NAME_SUBHEADING, $serviceInfoPostId)))
    @php($serviceInfoSidebarSelectedPostsRaw = get_field(\LidingoCustomisation\AcfFields\ServiceInfoSingleSidebarFields::FIELD_NAME_RELATED_POSTS, $serviceInfoPostId))
    @php($serviceInfoSidebarSelectedIds = array_values(array_filter(array_map(static fn($postId) => is_numeric($postId) ? (int) $postId : 0, is_array($serviceInfoSidebarSelectedPostsRaw) ? $serviceInfoSidebarSelectedPostsRaw : []))))
    @php($serviceInfoSidebarItems = [])
    @if (!empty($serviceInfoSidebarSelectedIds))
        @php($serviceInfoSidebarQuery = new \WP_Query([
            'post_type' => 'service_information',
            'post_status' => 'publish',
            'post__in' => $serviceInfoSidebarSelectedIds,
            'orderby' => 'post__in',
            'posts_per_page' => count($serviceInfoSidebarSelectedIds),
            'meta_query' => [
                'relation' => 'OR',
                \LidingoCustomisation\Helper\ServiceInfoStatus::getCurrentMetaQuery(),
                \LidingoCustomisation\Helper\ServiceInfoStatus::getPlannedMetaQuery(),
            ],
            'no_found_rows' => true,
        ]))
        @php($serviceInfoSidebarItems = array_values(array_filter(array_map(function ($relatedPost) {
            if (!$relatedPost instanceof \WP_Post) {
                return null;
            }

            $terms = get_the_terms($relatedPost->ID, 'service_category');
            $firstTerm = is_array($terms) ? reset($terms) : false;
            $iconAttachmentId = is_object($firstTerm) ? (int) get_field('icon', 'service_category_' . $firstTerm->term_id) : 0;
            $startDate = (string) get_field('start_date', $relatedPost->ID);
            $endDate = (string) get_field('end_date', $relatedPost->ID);
            $formattedDate = class_exists(\ModularityServiceInfo\Helper\DateFormatter::class)
                ? \ModularityServiceInfo\Helper\DateFormatter::formatDateRange($startDate, $endDate)
                : trim($startDate . ($endDate !== '' ? ' - ' . $endDate : ''));
            $formattedDate = str_replace(['&ndash;', '&#8211;', '–'], '-', (string) $formattedDate);
            $iconImageHtml = $iconAttachmentId > 0 ? wp_get_attachment_image($iconAttachmentId, 'thumbnail', false, [
                'class' => 'c-service-info-archive__icon-image',
                'alt' => '',
                'loading' => 'lazy',
                'decoding' => 'async',
            ]) : '';

            return [
                'title' => get_the_title($relatedPost->ID),
                'link' => get_permalink($relatedPost->ID),
                'formattedDate' => $formattedDate,
                'iconImageHtml' => is_string($iconImageHtml) ? $iconImageHtml : '',
            ];
        }, $serviceInfoSidebarQuery->posts))))
        @php(wp_reset_postdata())
    @endif
    @php($hasServiceInfoSidebarSection = !empty($serviceInfoSidebarItems))
    @php($hasRightSidebar = is_active_sidebar('right-sidebar'))
    @php($hasSidebarContent = $hasRightSidebar || $hasServiceInfoSidebarSection)

    <div class="o-container c-service-info-single{{ $hasSidebarContent ? ' c-service-info-single--has-sidebar' : '' }}">
        @includeIf('partials.sidebar', ['id' => 'content-area-top', 'classes' => ['o-grid']])

        <div class="c-service-info-single__helper u-print-display--none">
            @includeIf('partials.navigation.breadcrumb')
        </div>

        {!! $hook->loopStart !!}
        {!! $hook->innerLoopStart !!}

        <div class="c-service-info-single__grid">
            <article class="c-service-info-single__main">
                <header class="c-service-info-single__header">
                    @if (is_string($serviceInfoIconImageHtml) && $serviceInfoIconImageHtml !== '')
                        <span class="c-service-info-single__icon" aria-hidden="true">
                            {!! $serviceInfoIconImageHtml !!}
                        </span>
                    @endif

                    @if (!empty($serviceInfoTitle))
                        <h1 class="c-service-info-single__title">{!! $serviceInfoTitle !!}</h1>
                    @endif
                </header>

                @if ($serviceInfoFormattedDate !== '')
                    <p class="c-service-info-single__date">{{ $serviceInfoFormattedDate }}</p>
                @endif

                @if (!empty($serviceInfoContent))
                    <div class="c-service-info-single__content">
                        {!! $serviceInfoContent !!}
                    </div>
                @endif

                @if ($serviceInfoPublishedDate !== '')
                    <p class="c-service-info-single__published">
                        {{ __('Publiceringsdatum', 'lidingo-customisation') }}: {{ $serviceInfoPublishedDate }}
                    </p>
                @endif
            </article>

            @if ($hasSidebarContent)
                <aside class="c-service-info-single__aside" aria-label="{{ __('Högerkolumn', 'lidingo-customisation') }}">
                    <div class="c-service-info-single__aside-inner">
                        @if ($hasRightSidebar)
                            @includeIf('partials.sidebar', ['id' => 'right-sidebar'])
                        @endif

                        @if ($hasServiceInfoSidebarSection)
                            <section class="c-service-info-single__related">
                                <h2 class="c-service-info-single__related-heading">
                                    {{ $serviceInfoSidebarHeading !== '' ? $serviceInfoSidebarHeading : __('Övrig driftinformation', 'lidingo-customisation') }}
                                </h2>
                                <h3 class="c-service-info-single__related-subheading">
                                    {{ $serviceInfoSidebarSubheading !== '' ? $serviceInfoSidebarSubheading : __('Planerade arbeten', 'lidingo-customisation') }}
                                </h3>

                                <div class="c-service-info-single__related-items">
                                    @foreach ($serviceInfoSidebarItems as $item)
                                        <article class="c-service-info-archive__card">
                                            <a href="{{ $item['link'] }}" class="c-service-info-archive__card-link">
                                                @if (!empty($item['iconImageHtml']))
                                                    <div class="c-service-info-archive__icon-shell" aria-hidden="true">
                                                        <span class="c-service-info-archive__icon">
                                                            {!! $item['iconImageHtml'] !!}
                                                        </span>
                                                    </div>
                                                @endif

                                                <div class="c-service-info-archive__card-content">
                                                    @if (!empty($item['formattedDate']))
                                                        <p class="c-service-info-archive__date">{{ $item['formattedDate'] }}</p>
                                                    @endif

                                                    <h3 class="c-service-info-archive__card-title">{{ $item['title'] }}</h3>
                                                </div>
                                            </a>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                </aside>
            @endif
        </div>

        {!! $hook->innerLoopEnd !!}
        {!! $hook->loopEnd !!}

        @includeIf('partials.sidebar', ['id' => 'content-area-bottom', 'classes' => ['o-grid']])
    </div>
@stop
