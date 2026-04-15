@php($tableArguments = $getTableComponentArguments())
@php($archivePostType = !empty($archiveLayoutPostType) && is_string($archiveLayoutPostType) ? sanitize_key($archiveLayoutPostType) : '')
@php($shouldAddApplicationEndDateColumn = $archivePostType === 'job-listing')

@if ($shouldAddApplicationEndDateColumn && is_array($tableArguments))
    @php($headings = is_array($tableArguments['headings'] ?? null) ? $tableArguments['headings'] : [])
    @php($items = is_array($tableArguments['list'] ?? null) ? $tableArguments['list'] : [])

    @if (!empty($headings) && !empty($items))
        @php($headings[] = __('Ansök senast', 'lidingo-customisation'))

        @php(
            $items = array_map(
                static function (array $item): array {
                    $columns = is_array($item['columns'] ?? null) ? $item['columns'] : [];
                    $postId = isset($item['id']) ? (int) $item['id'] : 0;
                    $applicationEndDate = $postId > 0 ? trim((string) get_post_meta($postId, 'application_end_date', true)) : '';

                    if ($applicationEndDate !== '') {
                        $resolvedTimestamp = strtotime($applicationEndDate);
                        $applicationEndDate = $resolvedTimestamp !== false
                            ? wp_date((string) get_option('date_format', 'Y-m-d'), $resolvedTimestamp)
                            : $applicationEndDate;
                    }

                    $columns[] = $applicationEndDate;
                    $item['columns'] = $columns;

                    return $item;
                },
                $items
            )
        )

        @php($tableArguments['headings'] = $headings)
        @php($tableArguments['list'] = $items)
    @endif
@endif

@table([
    ...$tableArguments,
    'classList' => ['archive-list'],
    'context' => ['archive', 'archive.list', 'archive.list.list'],
    'attributeList' => ['data-js-posts-list-item' => true],
])
@endtable
