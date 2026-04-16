@if($archiveLinkAbovePosts)
    <div class="o-grid u-align-items--center">
        <div class="o-grid-9@md">
            @group([
                'direction' => 'horizontal',
                'justifyContent' => 'space-between',
                'alignItems' => 'center'
            ])
                @if (!$hideTitle && !empty($postTitle))
                    @typography([
                        'id' => 'mod-posts-' . $ID . '-label',
                        'element' => 'h2',
                        'variant' => 'h2',
                        'classList' => ['module-title']
                    ])
                        {!! $postTitle !!}
                    @endtypography
                @endif
                @if (!empty($titleCTA))
                    @icon($titleCTA)
                    @endicon
                @endif
            @endgroup
            @if ($preamble)
                @typography([
                    'classList' => ['module-preamble', 'u-margin__bottom--3']
                ])
                    {!! $preamble !!}
                @endtypography
            @endif
        </div>
        <div class="o-grid-3@md">
            @if (!empty($archiveLinkUrl))
                @link([
                    'href' => $archiveLinkUrl,
                    'classList' => ['u-display-block']
                ])
                    @group([
                        'classList' => [
                            'u-gap-1',
                            'u-margin__top--1@xs',
                            'u-margin__top--1@sm',
                            'u-justify-content--end@md',
                            'u-justify-content--end@lg',
                            'u-justify-content--end@xl',
                            'u-align-items--center'
                        ]
                    ])
                        {{ $archiveLinkTitle ?? $lang['showMore'] }}
                        @icon([
                            'icon' => 'trending_flat',
                            'size' => 'lg',
                        ])
                        @endicon
                    @endgroup
                @endlink
            @endif
        </div>
    </div>
@else
    @group([
        'direction' => 'horizontal',
        'justifyContent' => 'space-between',
        'alignItems' => 'center'
    ])
        @if (!$hideTitle && !empty($postTitle))
            @typography([
                'id' => 'mod-posts-' . $ID . '-label',
                'element' => 'h2',
                'variant' => 'h2',
                'classList' => ['module-title']
            ])
                {!! $postTitle !!}
            @endtypography
        @endif
        @if (!empty($titleCTA))
            @icon($titleCTA)
            @endicon
        @endif
    @endgroup
    @if ($preamble)
        @typography([
            'classList' => ['module-preamble', 'u-margin__bottom--3']
        ])
            {!! $preamble !!}
        @endtypography
    @endif
@endif

<div class="o-grid{{ !empty($stretch) ? ' o-grid--stretch' : '' }}{{ !empty($noGutter) ? ' o-grid--no-gutter' : '' }}{{ (!empty($preamble)||(!$hideTitle && !empty($postTitle))) ? ' u-margin__top--4' : '' }}"
@if (!$hideTitle && !empty($postTitle)) aria-labelledby="{{ 'mod-posts-' . $ID . '-label' }}" @endif>
    @if($posts)
        @foreach ($posts as $post)
            @php($postClassList = !empty($post->classList) && is_array($post->classList) ? array_values(array_filter(array_map(static fn ($class): string => is_string($class) ? trim($class) : '', $post->classList), static fn (string $class): bool => $class !== '')) : [])
            @php($postAttributeList = !empty($post->attributeList) && is_array($post->attributeList) ? $post->attributeList : [])
            <div
                @class($postClassList)
                @foreach ($postAttributeList as $attributeKey => $attributeValue)
                    @if (is_string($attributeKey) && preg_match('/^[A-Za-z_:][A-Za-z0-9:._-]*$/', $attributeKey) === 1 && !is_array($attributeValue) && !is_object($attributeValue))
                        {{ $attributeKey }}="{{ esc_attr(is_bool($attributeValue) ? ($attributeValue ? 'true' : 'false') : (string) $attributeValue) }}"
                    @endif
                @endforeach
            >
                @if ($highlight_first_column_as === 'block' && $post->isHighlighted)
                    @block([
                        'heading' => $post->postTitle,
                        'content' => $post->excerptShort,
                        'ratio' => '16:9',
                        'meta' => $post->termsUnlinked,
                        'secondaryMeta' => $post->readingTime,
                        'date' => $showDate ? [
                            'timestamp' => $post->getArchiveDateTimestamp(),
                            'format' => $post->getArchiveDateFormat(),
                        ] : null,
                        'dateBadge' => \Municipio\Helper\DateFormat::getUnresolvedDateFormat($post) == 'date-badge',
                        'image' => $post->getImage(),
                        'classList' => ['t-posts-block', ' u-height--100'],
                        'context' => ['module.posts.block'],
                        'link' => $post->getPermalink(),
                        'icon' => $post->getIcon() ? [
                            'icon' => $post->getIcon()->getIcon(),
                            'color' => 'white',
                        ] : null,
                        'iconBackgroundColor' => $post->getIcon() ? $post->getIcon()->getCustomColor() : null,
                        'attributeList' => $post->attributeList ?? []
                    ])
                        @if (!empty($post->callToActionItems['floating']['icon']))
                            @slot('floating')
                                @element($post->callToActionItems['floating']['wrapper'] ?? [])
                                    @icon($post->callToActionItems['floating']['icon'])
                                    @endicon
                                @endelement
                            @endslot
                        @endif
                        @slot('metaArea')
                            @if ($post->commentCount !== false)
                                @typography([
                                    'element' => 'span',
                                    'classList' => [
                                        'u-display--flex',
                                        'u-align-items--center',
                                        'u-font-size--meta'
                                    ]
                                ])
                                    @icon([
                                        'icon' => 'chat_bubble',
                                        'attributeList' => [
                                            'style' => 'margin-right: 4px;',
                                        ],
                                    ])
                                    @endicon
                                    {!! $post->commentCount !!}
                                @endtypography
                            @endif
                        @endslot
                    @endblock
                @else
                    @php($schemaType = method_exists($post, 'getSchemaProperty') ? $post->getSchemaProperty('@type') : null)
                    @php($postType = method_exists($post, 'getPostType') ? $post->getPostType() : '')
                    @php($isEventCard = in_array($schemaType, ['Event'], true) || in_array($postType, ['event', 'evenemang'], true))

                    @if ($isEventCard)
                        @php($attendanceMode = method_exists($post, 'getSchemaProperty') ? (string) ($post->getSchemaProperty('eventAttendanceMode') ?? '') : '')
                        @php($isOnlineOnly = str_contains($attendanceMode, 'OnlineEventAttendanceMode'))
                        @php($location = method_exists($post, 'getSchemaProperty') ? $post->getSchemaProperty('location') : null)
                        @php($locations = is_array($location) ? $location : (!empty($location) ? [$location] : []))
                        @php($firstLocation = $locations[0] ?? null)
                        @php($placeName = is_object($firstLocation) && method_exists($firstLocation, 'getProperty') ? (string) ($firstLocation->getProperty('name') ?: $firstLocation->getProperty('address') ?: '') : '')
                        @php($eventPlace = $isOnlineOnly ? 'online' : $placeName)
                        @php($eventSchedules = method_exists($post, 'getSchemaProperty') ? $post->getSchemaProperty('eventSchedule') : null)
                        @php($eventSchedules = is_array($eventSchedules) ? $eventSchedules : (!empty($eventSchedules) ? [$eventSchedules] : []))
                        @php(usort($eventSchedules, static fn ($a, $b) => ($a?->getProperty('startDate') ?? null) <=> ($b?->getProperty('startDate') ?? null)))
                        @php($now = new \DateTime('now'))
                        @php($upcomingSchedules = array_values(array_filter($eventSchedules, static fn ($schedule) => $schedule?->getProperty('startDate') instanceof \DateTimeInterface && $schedule->getProperty('startDate') >= $now)))
                        @php($pastSchedules = array_reverse(array_values(array_filter($eventSchedules, static fn ($schedule) => $schedule?->getProperty('startDate') instanceof \DateTimeInterface && $schedule->getProperty('startDate') < $now))))
                        @php($selectedSchedule = $upcomingSchedules[0] ?? $pastSchedules[0] ?? null)
                        @php($selectedStartDate = $selectedSchedule?->getProperty('startDate'))
                        @php($selectedEndDate = $selectedSchedule?->getProperty('endDate'))
                        @php($eventLink = $post->getPermalink())
                        @if ($selectedStartDate instanceof \DateTimeInterface)
                            @php($eventLink .= (str_contains($eventLink, '?') ? '&' : '?') . \Municipio\Controller\SingularEvent::CURRENT_OCCASION_GET_PARAM . '=' . $selectedStartDate->format(\Municipio\Controller\SingularEvent::CURRENT_OCCASION_DATE_FORMAT))
                        @endif
                        @php($eventBadgeDate = $selectedStartDate instanceof \DateTimeInterface ? $selectedStartDate->format(\Municipio\Helper\DateFormat::getDateFormat('date')) : null)
                        @php($eventDate = $selectedStartDate instanceof \DateTimeInterface ? wp_date(\Municipio\Helper\DateFormat::getDateFormat('date-time'), $selectedStartDate->getTimestamp()) : null)
                        @if ($eventDate && $selectedEndDate instanceof \DateTimeInterface)
                            @php($eventDate .= ' - ' . wp_date(\Municipio\Helper\DateFormat::getDateFormat('time'), $selectedEndDate->getTimestamp()))
                        @endif

                        @card([
                            'image' => $post->getImage(),
                            'link' => $eventLink,
                            'context' => ['module.posts.index'],
                            'containerAware' => true,
                            'hasPlaceholder' => $post->hasPlaceholderImage,
                            'classList' => ['u-height--100'],
                            'attributeList' => ['data-js-posts-list-item' => true],
                        ])
                            @if(!empty($eventBadgeDate))
                                @slot('floating')
                                    @datebadge(['date' => $eventBadgeDate, 'size' => 'sm'])@enddatebadge
                                @endslot
                            @endif

                            @slot('aboveContent')
                                @typography(['element' => 'h2', 'variant' => 'h4', 'classList' => ['u-margin__top--0']])
                                    {!! $post->getTitle() !!}
                                @endtypography

                                @element(['classList' => ['u-margin__bottom--0', 'u-margin__top--2', 'u-display--flex', 'u-flex-direction--column', 'o-layout-grid--gap-1']])
                                    @if(!empty($eventPlace))
                                        @typography(['variant' => 'meta', 'classList' => ['u-margin__top--0', 'u-margin__bottom--0', 'u-display--flex', 'u-align-items--center', 'o-layout-grid--gap-1']])
                                            @icon(['icon' => 'location_on', 'size' => 'sm'])@endicon
                                            {!! $eventPlace !!}
                                        @endtypography
                                    @endif
                                    @if(!empty($eventDate))
                                        @typography(['variant' => 'meta', 'classList' => ['u-margin__top--0', 'u-margin__bottom--0', 'u-display--flex', 'u-align-items--center', 'o-layout-grid--gap-1']])
                                            @icon(['icon' => 'event', 'size' => 'sm'])@endicon
                                            {!! $eventDate !!}
                                        @endtypography
                                    @endif
                                @endelement
                            @endslot
                        @endcard
                    @else
                        @card([
                            'link' => $post->getPermalink(),
                            'heading' => $post->getTitle(),
                            'context' => ['module.posts.index'],
                            'content' => $post->excerptShort,
                            'tags' => $post->termsUnlinked,
                            'date' => $showDate ? [
                                'timestamp' => $post->getArchiveDateTimestamp(),
                                'format' => $post->getArchiveDateFormat(),
                            ] : null,
                            'dateBadge' => \Municipio\Helper\DateFormat::getUnresolvedDateFormat($post) == 'date-badge',
                            'classList' => ['u-height--100'],
                            'containerAware' => true,
                            'hasPlaceholder' => $post->hasPlaceholderImage,
                            'image' => $post->getImage(),
                            'icon' => $post->getIcon() ? [
                                'icon' => $post->getIcon()->getIcon(),
                                'color' => 'white',
                            ] : null,
                            'iconBackgroundColor' => $post->getIcon() ? $post->getIcon()->getCustomColor() : null,
                            'attributeList' => $post->attributeList ?? []
                        ])
                            @slot('aboveContent')
                                @if (!empty($post->readingTime))
                                    @typography([
                                        'element' => 'span',
                                        'classList' => [
                                            'u-display--flex',
                                            'u-align-items--center',
                                            'u-font-size--meta'
                                        ]
                                    ])
                                        @icon([
                                            'icon' => 'timer',
                                            'size' => 'sm',
                                            'attributeList' => [
                                                'style' => 'margin-right: 4px;',
                                            ],
                                        ])
                                        @endicon {{ $post->readingTime }}
                                    @endtypography
                                @endif
                                @if ($post->commentCount !== false)
                                    @typography([
                                        'element' => 'span',
                                        'classList' => [
                                            'u-display--flex',
                                            'u-align-items--center',
                                            'u-font-size--meta'
                                        ]
                                    ])
                                        @icon([
                                            'icon' => 'chat_bubble',
                                            'attributeList' => [
                                                'style' => 'margin-right: 4px;',
                                            ],
                                        ])
                                        @endicon
                                        {!! $post->commentCount !!}
                                    @endtypography
                                @endif
                            @endslot

                            @if (!empty($post->callToActionItems['floating']['icon']))
                                @slot('floating')
                                    @element($post->callToActionItems['floating']['wrapper'] ?? [])
                                        @icon($post->callToActionItems['floating']['icon'])
                                        @endicon
                                    @endelement
                                @endslot
                            @endif
                        @endcard
                    @endif
                @endif
            </div>
        @endforeach
    @endif
</div>

@if(!$archiveLinkAbovePosts)
    @if (!empty($archiveLinkUrl))
        <div class="t-read-more-section u-display--flex u-align-content--center u-margin__y--4">
            @button([
              'text' => $archiveLinkTitle ?? $lang['showMore'],
              'color' => $archiveLinkStyle,
              'style' => 'filled',
              'href' => $archiveLinkUrl,
              'classList' => ['u-flex-grow--1@xs', 'u-margin__x--auto'],
              'icon' => $archiveLinkIcon,
            ])
            @endbutton
        </div>
    @endif
    @if($paginationArguments)
      <div class="u-margin__y--4">
        @pagination($paginationArguments)@endpagination
      </div>
    @endif
@endif
