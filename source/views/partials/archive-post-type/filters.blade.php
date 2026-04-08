<section class="c-post-type-archive__filter-shell">
    <h2 class="c-post-type-archive__filter-title">
        {{ __('Filtrera', 'lidingo-customisation') }}
    </h2>
    <style>
        html {
            scroll-behavior: unset !important;
        }
    </style>

    <div class="s-archive-filter">
        @form([
            'validation' => false,
            'method' => 'GET',
            'action' => '?q=form_component' . ($filterConfig->getAnchor() ? '#' . $filterConfig->getAnchor() . '_id' : '')
        ])

        @if ($filterConfig->isTextSearchEnabled())
            <div class="o-grid">
                <div class="o-grid-12">
                    @field([
                        ...$getTextSearchFieldArguments(),
                        'classList' => ['u-width--100'],
                    ])
                    @endfield
                </div>
            </div>
        @endif

        @if ($filterConfig->isDateFilterEnabled())
            <div class="o-grid">
                <div class="o-grid-12@xs o-grid-6@sm">
                    @field($getDateFilterFieldArguments()['from'])@endfield
                </div>
                <div class="o-grid-12@xs o-grid-6@sm">
                    @field($getDateFilterFieldArguments()['to'])@endfield
                </div>
            </div>
        @endif

        <div class="o-grid u-align-content--end">
            @if (!empty($yearOptions))
                <div class="o-grid-12@xs o-grid-6@sm o-grid-auto@md u-level-4">
                    @select([
                        'label' => __('År', 'lidingo-customisation'),
                        'name' => $archiveLayoutYearParameterName,
                        'required' => false,
                        'placeholder' => __('År', 'lidingo-customisation'),
                        'options' => $yearOptions,
                        'preselected' => !empty($archiveLayoutSelectedYear) ? [(string) $archiveLayoutSelectedYear] : [],
                        'size' => 'md',
                    ])
                    @endselect
                </div>
            @endif

            @foreach ($taxonomyFilterSelectArguments as $selectArguments)
                <div class="o-grid-12@xs o-grid-6@sm o-grid-auto@md u-level-4">
                    @select([...$selectArguments, 'size' => 'md'])@endselect
                </div>
            @endforeach

            <div class="o-grid-fit@xs o-grid-fit@sm o-grid-fit@md u-margin__top--auto">
                @button([
                    ...$getFilterFormSubmitButtonArguments(),
                    'text' => __('Filtrera', 'lidingo-customisation'),
                    'icon' => ':förbjudet:',
                    'color' => 'primary',
                    'classList' => ['u-display--block@xs', 'u-width--100@xs'],
                ])
                @endbutton
            </div>

            @if (!empty($archiveLayoutHasActiveFilters))
                <div class="o-grid-fit@xs o-grid-fit@sm o-grid-fit@md u-margin__top--auto">
                    @button([
                        ...$getFilterFormResetButtonArguments(),
                        'text' => __('Återställ filter', 'lidingo-customisation'),
                        'style' => 'outlined',
                        'color' => 'primary',
                        'href' => $archiveLayoutResetUrl,
                        'classList' => ['u-display--block@xs', 'u-width--100@xs'],
                    ])
                    @endbutton
                </div>
            @endif
        </div>

        @endform
    </div>
</section>
