@extends('templates.master')

@section('layout')
    @php($postId = get_the_ID())
    @php($isExpired = (string) get_post_meta($postId, 'has_expired', true) === '1')
    @php($applicationEndDate = (string) get_post_meta($postId, 'application_end_date', true))
    @php($daysLeft = !empty($applicationEndDate) ? max(0, (int) floor((strtotime($applicationEndDate . ' 23:59:59') - current_time('timestamp')) / DAY_IN_SECONDS)) : 0)
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
        ['label' => __('Sista ansökningsdag', 'lidingo-customisation'), 'value' => $applicationEndDate !== '' ? $applicationEndDate . ' (' . $daysLeft . ' ' . $daysLeftLabel . ')' : ''],
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
                    <div class="c-content-page__aside-inner c-job-sidebar">
                        @if(!empty($informationItems))
                            <div class="o-grid-12">
                                @card(['classList' => ['c-card--panel', 'c-job-sidebar__card']])
                                    @collection()
                                        @collection__item([])
                                            @typography(['element' => 'h2', 'variant' => 'h2'])
                                                {{ __('Information', 'lidingo-customisation') }}
                                            @endtypography
                                        @endcollection__item

                                        @foreach($informationItems as $item)
                                            @collection__item([])
                                                @typography(['element' => 'h3', 'variant' => 'h4'])
                                                    {{ $item['label'] }}
                                                @endtypography
                                                @typography(['element' => 'p'])
                                                    {{ $item['value'] }}
                                                @endtypography
                                            @endcollection__item
                                        @endforeach
                                    @endcollection
                                @endcard
                            </div>
                        @endif

                        @if(!empty($contacts))
                            <div class="o-grid-12">
                                @card(['classList' => ['c-card--panel', 'c-job-sidebar__card']])
                                    @collection()
                                        @collection__item([])
                                            @typography(['element' => 'h2', 'variant' => 'h2'])
                                                {{ __('Kontakt', 'lidingo-customisation') }}
                                            @endtypography
                                        @endcollection__item

                                        @foreach($contacts as $contact)
                                            @php($name = (string) ($contact['name'] ?? ''))
                                            @php($position = (string) ($contact['position'] ?? ''))
                                            @php($phone = (string) ($contact['phone'] ?? ''))
                                            @php($phoneSanitized = (string) ($contact['phone_sanitized'] ?? preg_replace('/\D/', '', $phone)))
                                            @php($email = (string) ($contact['email'] ?? ''))

                                            @if(!empty($name) || !empty($position) || !empty($phone) || !empty($email))
                                                @collection__item([])
                                                    @if(!empty($name))
                                                        @typography(['element' => 'h3', 'variant' => 'h4'])
                                                            {{ $name }}
                                                        @endtypography
                                                    @endif

                                                    @if(!empty($position))
                                                        @typography(['variant' => 'meta', 'element' => 'span'])
                                                            {{ $position }}
                                                        @endtypography
                                                    @endif

                                                    @if(!empty($phone))
                                                        @typography(['element' => 'p'])
                                                            <span class="c-job-sidebar__contact-token" aria-hidden="true">:telefon:</span>
                                                            <a href="tel:{{ $phoneSanitized }}">{{ $phone }}</a>
                                                        @endtypography
                                                    @endif

                                                    @if(!empty($email))
                                                        @typography(['element' => 'p'])
                                                            <span class="c-job-sidebar__contact-token" aria-hidden="true">:mail:</span>
                                                            <a href="mailto:{{ $email }}">{{ $email }}</a>
                                                        @endtypography
                                                    @endif
                                                @endcollection__item
                                            @endif
                                        @endforeach
                                    @endcollection
                                @endcard
                            </div>
                        @endif

                        <div class="o-grid-12">
                            @if($isExpired || empty($applyLink))
                                @button([
                                    'text' => __('Ansökningstiden har gått ut', 'lidingo-customisation'),
                                    'style' => 'filled',
                                    'attributeList' => ['disabled' => 'true']
                                ])
                                @endbutton
                            @else
                                @button([
                                    'text' => __('Ansök nu', 'lidingo-customisation'),
                                    'color' => 'primary',
                                    'style' => 'filled',
                                    'href' => $applyLink,
                                    'icon' => ':höger:',
                                    'classList' => ['c-button--margin-top', 'u-margin__right--1']
                                ])
                                @endbutton
                            @endif
                        </div>
                    </div>
                </aside>
            @endif
        </div>
        <div class="c-content-page__below">
            @includeIf('partials.sidebar', ['id' => 'content-area', 'classes' => ['o-grid']])
        </div>
    </div>
@stop
