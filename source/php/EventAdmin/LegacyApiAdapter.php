<?php

declare(strict_types=1);

namespace LidingoCustomisation\EventAdmin;

use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

class LegacyApiAdapter
{
    private const JSON_REWRITE_VERSION = '1';

    public function addHooks(): void
    {
        add_action('init', [$this, 'registerJsonRewrite']);
        add_action('init', [$this, 'maybeFlushJsonRewrite'], 20);
        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_action('admin_notices', [$this, 'renderEditorialNotice']);
    }

    public function registerJsonRewrite(): void
    {
        if (!$this->isEventAdminSite()) {
            return;
        }

        add_rewrite_rule('^json/?$', 'index.php?rest_route=/', 'top');
        add_rewrite_rule('^json/(.*)?', 'index.php?rest_route=/$matches[1]', 'top');
    }

    public function maybeFlushJsonRewrite(): void
    {
        if (!$this->isEventAdminSite()) {
            return;
        }

        $optionKey = 'lidingo_eventadmin_json_rewrite_version';
        if (get_option($optionKey) === self::JSON_REWRITE_VERSION) {
            return;
        }

        flush_rewrite_rules(false);
        update_option($optionKey, self::JSON_REWRITE_VERSION, false);
    }

    public function registerRoutes(): void
    {
        if (!$this->isEventAdminSite()) {
            return;
        }

        register_rest_route('wp/v2', '/event/time', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getEventsByTime'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wp/v2', '/event', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getEvents'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wp/v2', '/event/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getEvent'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wp/v2', '/location', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getLocations'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wp/v2', '/organizer', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getOrganizers'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wp/v2', '/user_groups', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getUserGroups'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wp/v2', '/membership-card', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getMembershipCards'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function getEventsByTime(WP_REST_Request $request): WP_REST_Response
    {
        return $this->buildEventCollectionResponse($request, true);
    }

    public function getEvents(WP_REST_Request $request): WP_REST_Response
    {
        return $this->buildEventCollectionResponse($request, false);
    }

    public function getEvent(WP_REST_Request $request): WP_REST_Response
    {
        $post = get_post((int) $request['id']);

        if (!$post instanceof WP_Post || $post->post_type !== 'event' || $post->post_status !== 'publish') {
            return new WP_REST_Response([
                'code'    => 'empty_result',
                'message' => __('No events could be found.', 'lidingo-customisation'),
            ], 404);
        }

        return new WP_REST_Response($this->mapEvent($post), 200);
    }

    public function getLocations(WP_REST_Request $request): WP_REST_Response
    {
        $locations = [];

        foreach ($this->getPublishedEventPosts() as $post) {
            $location = $this->mapLocation($post->ID);
            if (empty($location)) {
                continue;
            }

            $key = md5(wp_json_encode($location));
            $locations[$key] = [
                'id'           => abs(crc32($key)),
                'title'        => $location['title'] ?? '',
                'street_address' => $location['street_address'] ?? '',
                'postal_code'  => $location['postal_code'] ?? '',
                'city'         => $location['city'] ?? '',
                'latitude'     => $location['latitude'] ?? null,
                'longitude'    => $location['longitude'] ?? null,
            ];
        }

        return $this->buildPaginatedResponse(array_values($locations), $request);
    }

    public function getOrganizers(WP_REST_Request $request): WP_REST_Response
    {
        $terms = get_terms([
            'taxonomy'   => 'organization',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return $this->buildPaginatedResponse([], $request);
        }

        $organizers = array_map(function ($term) {
            $prefix = 'organization_' . $term->term_id;

            return [
                'id'        => (int) $term->term_id,
                'name'      => $term->name,
                'slug'      => $term->slug,
                'email'     => (string) get_field('email', $prefix),
                'telephone' => (string) get_field('telephone', $prefix),
                'contact'   => (string) get_field('contact', $prefix),
                'address'   => (string) get_field('address', $prefix),
                'url'       => (string) get_field('url', $prefix),
            ];
        }, $terms);

        return $this->buildPaginatedResponse($organizers, $request);
    }

    public function getUserGroups(WP_REST_Request $request): WP_REST_Response
    {
        return $this->buildPaginatedResponse([], $request);
    }

    public function getMembershipCards(WP_REST_Request $request): WP_REST_Response
    {
        return $this->buildPaginatedResponse([], $request);
    }

    public function renderEditorialNotice(): void
    {
        if (!$this->isEventAdminSite() || !is_admin()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->post_type !== 'event') {
            return;
        }

        echo wp_kses_post(
            '<div class="notice notice-info"><p><strong>' .
            esc_html__('Lidingo event standard', 'lidingo-customisation') .
            '</strong><br>' .
            esc_html__('Use the first image as the main hero image, add at least one occasion, always set an organizer, and only choose accessibility values that are explicitly confirmed for the event.', 'lidingo-customisation') .
            '</p></div>'
        );
    }

    private function buildEventCollectionResponse(WP_REST_Request $request, bool $filterByTime): WP_REST_Response
    {
        $events = [];
        $startTimestamp = $this->parseDateParameter((string) $request->get_param('start'));
        $endTimestamp = $this->parseDateParameter((string) $request->get_param('end'), true);

        foreach ($this->getPublishedEventPosts() as $post) {
            $mappedEvent = $this->mapEvent($post);
            if (empty($mappedEvent['occasions'])) {
                continue;
            }

            if ($filterByTime && !$this->eventMatchesInterval($mappedEvent['occasions'], $startTimestamp, $endTimestamp)) {
                continue;
            }

            $events[] = $mappedEvent;
        }

        usort($events, function (array $left, array $right): int {
            $leftStart = $left['occasions'][0]['start_date'] ?? '';
            $rightStart = $right['occasions'][0]['start_date'] ?? '';

            return strcmp($leftStart, $rightStart);
        });

        if (empty($events)) {
            return new WP_REST_Response([
                'code'    => 'empty_result',
                'message' => __('No events could be found.', 'lidingo-customisation'),
            ], 404);
        }

        return $this->buildPaginatedResponse($events, $request);
    }

    /**
     * @return WP_Post[]
     */
    private function getPublishedEventPosts(): array
    {
        $query = new WP_Query([
            'post_type'              => 'event',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        return array_values(array_filter($query->posts, fn ($post) => $post instanceof WP_Post));
    }

    private function buildPaginatedResponse(array $items, WP_REST_Request $request): WP_REST_Response
    {
        $perPage = max(1, (int) ($request->get_param('per_page') ?: 25));
        $page = max(1, (int) ($request->get_param('page') ?: 1));
        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $pagedItems = array_slice($items, $offset, $perPage);

        $response = new WP_REST_Response($pagedItems, 200);
        $response->header('X-WP-Total', (string) $total);
        $response->header('X-WP-TotalPages', (string) $totalPages);

        return $response;
    }

    private function mapEvent(WP_Post $post): array
    {
        $postId = $post->ID;
        $gallery = $this->getGalleryUrls($postId);
        $featuredImage = $gallery[0]['url'] ?? null;
        $organizers = $this->mapOrganizers($postId);
        $location = $this->mapLocation($postId);
        $occasions = $this->buildOccasions($postId);
        $accessibility = wp_get_post_terms($postId, 'accessibility', ['fields' => 'names']);
        $categories = wp_get_post_terms($postId, 'category', ['fields' => 'names']);
        $keywords = wp_get_post_terms($postId, 'keyword', ['fields' => 'names']);

        return [
            'id'               => $postId,
            'date_gmt'         => get_post_time('Y-m-d H:i:s', true, $post),
            'modified_gmt'     => get_post_modified_time('Y-m-d H:i:s', true, $post),
            'title'            => ['rendered' => get_the_title($post)],
            'content'          => ['rendered' => apply_filters('the_content', $post->post_content)],
            'featured_media'   => ['source_url' => $featuredImage],
            'gallery'          => $gallery,
            'event_categories' => is_array($categories) ? array_values($categories) : [],
            'event_tags'       => is_array($keywords) ? array_values($keywords) : [],
            'user_groups'      => [],
            'occasions'        => $occasions,
            'location'         => $location,
            'organizers'       => $organizers,
            'accessibility'    => is_array($accessibility) ? array_values($accessibility) : [],
            'event_link'       => get_field('attendancemode', $postId) === 'online'
                ? (string) get_field('onlineAttendenceUrl', $postId)
                : null,
        ];
    }

    private function getGalleryUrls(int $postId): array
    {
        $images = get_field('images', $postId);
        if (!is_array($images)) {
            return [];
        }

        $gallery = [];
        foreach ($images as $imageId) {
            $url = wp_get_attachment_image_url((int) $imageId, 'full');
            if (!$url) {
                continue;
            }

            $gallery[] = ['url' => $url];
        }

        return $gallery;
    }

    private function buildOccasions(int $postId): array
    {
        $rows = get_field('occasions', $postId);
        if (!is_array($rows)) {
            return [];
        }

        $occasions = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $repeatMode = (string) ($row['repeat'] ?? 'no');
            if ($repeatMode === 'byWeek') {
                $occasions = array_merge($occasions, $this->expandWeeklyOccasions($row));
                continue;
            }

            $occasion = $this->formatOccasion($row, (string) ($row['date'] ?? ''));
            if ($occasion !== null) {
                $occasions[] = $occasion;
            }
        }

        usort($occasions, fn (array $left, array $right): int => strcmp($left['start_date'], $right['start_date']));

        return $occasions;
    }

    private function expandWeeklyOccasions(array $row): array
    {
        $startDate = (string) ($row['date'] ?? '');
        $untilDate = (string) ($row['untilDate'] ?? '');
        $weekDays = is_array($row['weekDays'] ?? null) ? $row['weekDays'] : [];
        $weeksInterval = max(1, (int) ($row['weeksInterval'] ?? 1));

        if ($startDate === '' || $untilDate === '' || empty($weekDays)) {
            return [];
        }

        $start = strtotime($startDate);
        $until = strtotime($untilDate);
        if ($start === false || $until === false || $until < $start) {
            return [];
        }

        $allowedWeekdays = array_map([$this, 'normalizeSchemaWeekday'], $weekDays);
        $allowedWeekdays = array_filter($allowedWeekdays, fn (?int $weekday): bool => $weekday !== null);

        $occasions = [];
        for ($timestamp = $start; $timestamp <= $until; $timestamp = strtotime('+1 day', $timestamp)) {
            $weekday = (int) date('N', $timestamp);
            if (!in_array($weekday, $allowedWeekdays, true)) {
                continue;
            }

            $weekOffset = (int) floor((($timestamp - $start) / DAY_IN_SECONDS) / 7);
            if ($weekOffset % $weeksInterval !== 0) {
                continue;
            }

            $occasion = $this->formatOccasion($row, gmdate('Y-m-d', $timestamp));
            if ($occasion !== null) {
                $occasions[] = $occasion;
            }
        }

        return $occasions;
    }

    private function formatOccasion(array $row, string $date): ?array
    {
        $startTime = (string) ($row['startTime'] ?? '');
        $endTime = (string) ($row['endTime'] ?? '');

        if ($date === '' || $startTime === '' || $endTime === '') {
            return null;
        }

        $startDateTime = $date . ' ' . $startTime;
        $endDateTime = $date . ' ' . $endTime;

        if (strtotime($endDateTime) <= strtotime($startDateTime)) {
            $endDateTime = date('Y-m-d H:i:s', strtotime($endDateTime . ' +1 day'));
        }

        $description = trim((string) ($row['description'] ?? ''));
        $bookingUrl = trim((string) ($row['url'] ?? ''));

        return [
            'start_date'             => date('Y-m-d H:i:s', strtotime($startDateTime)),
            'end_date'               => date('Y-m-d H:i:s', strtotime($endDateTime)),
            'door_time'              => null,
            'status'                 => null,
            'occ_exeption_information' => null,
            'content_mode'           => $description !== '' ? 'custom' : null,
            'content'                => $description !== '' ? wpautop($description) : null,
            'location_mode'          => null,
            'location'               => null,
            'booking_link'           => $bookingUrl !== '' ? esc_url_raw($bookingUrl) : null,
        ];
    }

    private function mapLocation(int $postId): array
    {
        $attendanceMode = (string) get_field('attendancemode', $postId);
        if ($attendanceMode === 'online') {
            return [
                'title'          => __('Online event', 'lidingo-customisation'),
                'street_address' => '',
                'postal_code'    => '',
                'city'           => '',
            ];
        }

        $locationName = (string) get_field('locationName', $postId);
        $address = get_field('locationAddress', $postId);
        if (!is_array($address)) {
            $address = [];
        }

        return array_filter([
            'title'          => $locationName,
            'street_address' => $this->buildStreetAddress($address),
            'postal_code'    => (string) ($address['post_code'] ?? ''),
            'city'           => (string) ($address['city'] ?? $address['address_locality'] ?? ''),
            'latitude'       => isset($address['lat']) ? (string) $address['lat'] : null,
            'longitude'      => isset($address['lng']) ? (string) $address['lng'] : null,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function buildStreetAddress(array $address): string
    {
        $streetNumber = trim((string) ($address['street_number'] ?? ''));
        $streetName = trim((string) ($address['street_name'] ?? ''));

        $street = trim($streetName . ' ' . $streetNumber);
        if ($street !== '') {
            return $street;
        }

        return (string) ($address['address'] ?? '');
    }

    private function mapOrganizers(int $postId): array
    {
        $terms = wp_get_post_terms($postId, 'organization');
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        return array_map(function ($term): array {
            $prefix = 'organization_' . $term->term_id;

            return array_filter([
                'organizer'        => $term->name,
                'organizer_phone'  => (string) get_field('telephone', $prefix),
                'organizer_email'  => (string) get_field('email', $prefix),
                'organizer_link'   => (string) get_field('url', $prefix),
                'organizer_contact'=> (string) get_field('contact', $prefix),
                'organizer_address'=> (string) get_field('address', $prefix),
            ], static fn ($value): bool => $value !== '');
        }, $terms);
    }

    private function eventMatchesInterval(array $occasions, ?int $startTimestamp, ?int $endTimestamp): bool
    {
        if ($startTimestamp === null && $endTimestamp === null) {
            return true;
        }

        foreach ($occasions as $occasion) {
            $occasionStart = strtotime((string) ($occasion['start_date'] ?? ''));
            $occasionEnd = strtotime((string) ($occasion['end_date'] ?? ''));

            if ($occasionStart === false || $occasionEnd === false) {
                continue;
            }

            $startsAfterInterval = $endTimestamp !== null && $occasionStart > $endTimestamp;
            $endsBeforeInterval = $startTimestamp !== null && $occasionEnd < $startTimestamp;

            if (!$startsAfterInterval && !$endsBeforeInterval) {
                return true;
            }
        }

        return false;
    }

    private function parseDateParameter(string $date, bool $endOfDay = false): ?int
    {
        if ($date === '') {
            return null;
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        if ($endOfDay && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return strtotime(date('Y-m-d 23:59:59', $timestamp));
        }

        return $timestamp;
    }

    private function normalizeSchemaWeekday(string $weekday): ?int
    {
        $day = basename($weekday);

        return match ($day) {
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
            default => null,
        };
    }

    private function isEventAdminSite(): bool
    {
        $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        $path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

        return str_starts_with($host, 'eventadmin.') || $path === 'eventadmin';
    }
}
