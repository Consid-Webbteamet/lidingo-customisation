<?php

declare(strict_types=1);

namespace LidingoCustomisation\Archives;

use WP_Post;

class OngoingWorkArchive
{
    private const POST_TYPE = 'pagaende-arbeten';

    public function addHooks(): void
    {
        add_filter('Municipio/Template/viewData', [$this, 'customizeViewData'], 20);
    }

    public function customizeViewData(array $viewData): array
    {
        if (!$this->isOngoingWorkArchive()) {
            return $viewData;
        }

        $page = $this->getArchivePage();
        $pageId = $page?->ID;

        $viewData['showSidebars'] = false;
        $viewData['hasSideMenu'] = false;
        $viewData['helperNavBeforeContent'] = false;
        $viewData['skipToMainContentLink'] = '#main-content';
        $viewData['ongoingWorkArchivePageId'] = $pageId;
        $viewData['ongoingWorkArchiveTitle'] = $this->getArchiveTitle($page, $viewData);
        $viewData['ongoingWorkArchiveLead'] = $this->getArchiveLead($page, $viewData);
        $viewData['ongoingWorkArchiveContent'] = $this->getArchiveContent($page);
        $viewData['ongoingWorkArchiveImageHtml'] = $this->getArchiveImageHtml($page);
        $viewData['breadcrumbMenu'] = $this->getBreadcrumbMenu($viewData, $pageId);

        return $viewData;
    }

    private function isOngoingWorkArchive(): bool
    {
        return is_post_type_archive(self::POST_TYPE);
    }

    private function getArchivePage(): ?WP_Post
    {
        $pageId = get_option('page_for_' . self::POST_TYPE);

        if (!is_numeric($pageId) || (int) $pageId <= 0) {
            return null;
        }

        $page = get_post((int) $pageId);

        return $page instanceof WP_Post && $page->post_type === 'page'
            ? $page
            : null;
    }

    private function getArchiveTitle(?WP_Post $page, array $viewData): string
    {
        if ($page instanceof WP_Post) {
            $title = get_the_title($page);

            if (is_string($title) && $title !== '') {
                return $title;
            }
        }

        if (!empty($viewData['archiveTitle']) && is_string($viewData['archiveTitle'])) {
            return $viewData['archiveTitle'];
        }

        $postTypeObject = get_post_type_object(self::POST_TYPE);

        return is_object($postTypeObject) && !empty($postTypeObject->labels->name)
            ? (string) $postTypeObject->labels->name
            : '';
    }

    private function getArchiveLead(?WP_Post $page, array $viewData): string
    {
        if ($page instanceof WP_Post) {
            $excerpt = get_post_field('post_excerpt', $page);

            if (is_string($excerpt) && $excerpt !== '') {
                return (string) apply_filters('the_excerpt', $excerpt);
            }
        }

        if (!empty($viewData['archiveLead']) && is_string($viewData['archiveLead'])) {
            return wpautop($viewData['archiveLead']);
        }

        return '';
    }

    private function getArchiveContent(?WP_Post $page): string
    {
        if (!$page instanceof WP_Post) {
            return '';
        }

        $content = (string) $page->post_content;

        if ($content === '') {
            return '';
        }

        return (string) apply_filters('the_content', $content);
    }

    private function getArchiveImageHtml(?WP_Post $page): string
    {
        if (!$page instanceof WP_Post) {
            return '';
        }

        $thumbnailId = get_post_thumbnail_id($page);

        if (!is_numeric($thumbnailId) || (int) $thumbnailId <= 0) {
            return '';
        }

        $imageHtml = wp_get_attachment_image(
            (int) $thumbnailId,
            'large',
            false,
            [
                'class' => 'c-ongoing-work-archive__hero-image',
                'loading' => 'eager',
                'decoding' => 'async',
            ]
        );

        return is_string($imageHtml) ? $imageHtml : '';
    }

    private function getBreadcrumbMenu(array $viewData, ?int $pageId): array
    {
        $breadcrumbMenu = is_array($viewData['breadcrumbMenu'] ?? null)
            ? $viewData['breadcrumbMenu']
            : [];

        if (!$pageId || $pageId <= 0) {
            return $breadcrumbMenu;
        }

        $existingItems = array_values(is_array($breadcrumbMenu['items'] ?? null) ? $breadcrumbMenu['items'] : []);
        $items = [];

        if (!empty($existingItems[0]) && is_array($existingItems[0])) {
            $items[] = $existingItems[0];
        } else {
            $items[] = [
                'label' => __('Home', 'municipio'),
                'href' => home_url('/'),
                'current' => false,
                'icon' => 'home',
            ];
        }

        $frontPageId = (int) get_option('page_on_front');
        $ancestorIds = array_reverse(array_filter(array_map('intval', get_post_ancestors($pageId))));

        foreach ($ancestorIds as $ancestorId) {
            if ($ancestorId <= 0 || $ancestorId === $frontPageId) {
                continue;
            }

            $items[] = [
                'label' => get_the_title($ancestorId) ?: __('Untitled page', 'municipio'),
                'href' => get_permalink($ancestorId),
                'current' => false,
                'icon' => 'chevron_right',
            ];
        }

        $items[] = [
            'label' => get_the_title($pageId) ?: __('Untitled page', 'municipio'),
            'href' => get_permalink($pageId),
            'current' => true,
            'icon' => 'chevron_right',
        ];

        $breadcrumbMenu['items'] = $items;

        return $breadcrumbMenu;
    }
}
