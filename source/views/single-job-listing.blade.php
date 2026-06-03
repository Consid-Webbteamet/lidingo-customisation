@extends('templates.master')

@section('layout')
    @php($postId = get_the_ID())
    @php($applicationEndDate = (string) get_post_meta($postId, 'application_end_date', true))
    @php($publishEndDate = (string) get_post_meta($postId, 'publish_end_date', true))
    @php($effectiveEndDate = trim($applicationEndDate !== '' ? $applicationEndDate : $publishEndDate))
    @php($effectiveEndDateTime = $effectiveEndDate !== '' ? date_create_immutable($effectiveEndDate, wp_timezone()) : false)
    @php($effectiveEndTimestamp = $effectiveEndDateTime instanceof \DateTimeImmutable ? $effectiveEndDateTime->setTime(23, 59, 59)->getTimestamp() : false)
    @php($currentTimestamp = time())
    @php($isExpired = $effectiveEndTimestamp !== false ? $currentTimestamp > $effectiveEndTimestamp : (string) get_post_meta($postId, 'has_expired', true) === '1')
    @php($daysLeft = $effectiveEndTimestamp !== false ? max(0, (int) floor(($effectiveEndTimestamp - $currentTimestamp) / DAY_IN_SECONDS)) : 0)
    @php($reference = (string) get_post_meta($postId, 'ad_reference_nbr', true))
    @php($published = (string) get_post_meta($postId, 'publish_start_date', true))
    @php($employmentGrade = (string) get_post_meta($postId, 'employment_grade', true))
    @php($locationNameRaw = get_post_meta($postId, 'location_name', true))
    @php($locationName = is_array($locationNameRaw) ? implode(', ', array_filter(array_map(static fn($value): string => trim((string) $value), $locationNameRaw), static fn(string $value): bool => $value !== '')) : trim((string) $locationNameRaw))
    @php($departmentsRaw = get_post_meta($postId, 'departments', true))
    @php($departments = is_array($departmentsRaw) ? implode(' - ', array_filter(array_map(static fn($value): string => trim((string) $value), $departmentsRaw), static fn(string $value): bool => $value !== '')) : trim((string) $departmentsRaw))
    @php($contacts = get_post_meta($postId, 'contact', true))
    @php($contacts = is_array($contacts) ? $contacts : [])
    @php($applyLink = (string) get_post_meta($postId, 'external_url', true))
    @php($preamble = (string) get_post_meta($postId, 'preamble', true))
    @php($content = apply_filters('the_content', get_post_field('post_content', $postId)))

    @php($daysLeftLabel = $daysLeft === 1 ? __('dag kvar', 'lidingo-customisation') : __('dagar kvar', 'lidingo-customisation'))
    @php($informationItems = array_values(array_filter([
        ['label' => __('Sista ansökningsdag', 'lidingo-customisation'), 'value' => $effectiveEndDate !== '' ? $effectiveEndDate . ' (' . $daysLeft . ' ' . $daysLeftLabel . ')' : ''],
        ['label' => __('Omfattning', 'lidingo-customisation'), 'value' => $employmentGrade],
        ['label' => __('Publicerad', 'lidingo-customisation'), 'value' => $published],
        ['label' => __('Ort', 'lidingo-customisation'), 'value' => $locationName],
        ['label' => __('Förvaltning', 'lidingo-customisation'), 'value' => $departments],
        ['label' => __('Referens', 'lidingo-customisation'), 'value' => $reference],
    ], static fn(array $item): bool => !empty(trim((string) $item['value'])))))
    @php($hasSidebarContent = !empty($informationItems) || !empty($contacts) || $isExpired || !empty($applyLink))

    <div class="o-container c-content-page">
        <div class="c-content-page__grid">
            <div class="c-content-page__main">
                <div class="c-content-page__main-inner">
                    <div class="c-content-page__helper u-print-display--none">
                        @includeIf('partials.navigation.breadcrumb')

                        <div class="c-content-page__print">
                            <button
                                type="button"
                                class="button button--ghost c-content-page__print-button"
                                onclick="window.print()"
                                aria-label="{{ __('Skriv ut', 'lidingo-customisation') }}"
                            >
                                <span class="c-button__label">
                                    <span class="c-button__label-text">{{ __('Skriv ut', 'lidingo-customisation') }}</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    @if($isExpired)
                        <div class="gutter gutter-bottom">
                            @notice([
                                'type' => 'warning',
                                'message' => [
                                    'text' => __('The application period for this recruitment has ended.', 'job-listings'),
                                    'size' => 'md'
                                ],
                                'icon' => [
                                    'name' => 'report',
                                    'size' => 'md',
                                    'color' => 'white'
                                ]
                            ])
                            @endnotice
                        </div>
                    @endif

                    {!! $hook->innerLoopStart !!}

                    <div class="c-content-page__content-inner">
                        @typography(["element" => "h1", "classList" => ["c-content-page__title"]])
                            {{ get_the_title($postId) }}
                        @endtypography

                        @if(!empty($preamble))
                            <div class="c-content-page__preamble lead">
                                {!! nl2br($preamble) !!}
                            </div>
                        @endif

                        <div class="c-content-page__content">
                            {!! $content !!}
                        </div>

                        @includeIf('partials.sidebar', ['id' => 'content-area-bottom', 'classes' => ['o-grid']])
                    </div>

                    {!! $hook->innerLoopEnd !!}
                </div>
            </div>

            @if($hasSidebarContent)
                <aside class="c-content-page__aside">
                    @includeIf('partials.job-sidebar', [
                        'informationItems' => $informationItems,
                        'contacts' => $contacts,
                        'isExpired' => $isExpired,
                        'applyLink' => $applyLink,
                    ])
                </aside>
            @endif
        </div>
        <div class="c-content-page__below">
            @includeIf('partials.sidebar', ['id' => 'content-area', 'classes' => ['o-grid']])
        </div>
    </div>
@stop
