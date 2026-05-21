<?php

declare(strict_types=1);

namespace LidingoCustomisation\Navigation;

class DrawerMenuAppend
{
    /** Register the mobile drawer menu augmentation. */
    public function addHooks(): void
    {
        add_filter('Municipio/viewData', [$this, 'appendMenusToDrawer'], 20, 1);
    }

    /** Expose primary and language menu links for a promoted drawer wrapper. */
    public function appendMenusToDrawer(array $data): array
    {
        $data['mobileMenu']['items'] = $data['mobileMenu']['items'] ?? [];
        $existingIds = array_column($data['mobileMenu']['items'], 'id');
        $appendedItems = [];

        $items = array_merge(
            $data['primaryMenu']['items'] ?? [],
            $data['languageMenu']['items'] ?? []
        );

        foreach ($items as $item) {
            if (!is_array($item) || !$this->isAppendableItem($item)) {
                continue;
            }

            $drawerItemId = $this->getDrawerItemId($item);

            if (in_array($drawerItemId, $existingIds, true)) {
                continue;
            }

            $appendedItems[] = $this->prepareDrawerItem($item, $drawerItemId);
            $existingIds[] = $drawerItemId;
        }

        $data['mobileMenu']['promotedItems'] = $appendedItems;

        return $data;
    }

    /** Keep only top-level links with usable labels and URLs. */
    private function isAppendableItem(array $item): bool
    {
        return ($item['top_level'] ?? false) === true
            && !empty($item['href'])
            && !empty($item['label']);
    }

    /** Prepare an appended drawer link without icons or child toggles. */
    private function prepareDrawerItem(array $item, string $drawerItemId): array
    {
        $item['id'] = $drawerItemId;
        $item['post_parent'] = 0;
        $item['children'] = false;
        $item['style'] = 'default';
        $item['icon'] = $this->normalizeDrawerIcon($item['icon'] ?? [], $item);
        $item['classList'] = $this->normalizeDrawerClassList($item['classList'] ?? []);

        return $item;
    }

    /** Keep menu icons, and provide a language icon when the language item has none. */
    private function normalizeDrawerIcon(array $icon, array $item): array
    {
        if (!empty($icon['icon'])) {
            return $icon;
        }

        if (($item['post_type'] ?? '') === 'service-info-archive') {
            return [
                'icon'      => 'warning',
                'size'      => 'md',
                'classList' => ['c-nav__icon'],
            ];
        }

        if ($this->isLanguageItem($item)) {
            return [
                'icon'      => 'language',
                'size'      => 'md',
                'classList' => ['c-nav__icon'],
            ];
        }

        return [];
    }

    /** Detect language service links that should render with the language icon. */
    private function isLanguageItem(array $item): bool
    {
        $href = (string) ($item['href'] ?? '');

        return ($item['post_type'] ?? '') === 'language'
            || str_contains($href, 'translate.google.com');
    }

    /** Remove language/tile presentation classes so appended links match the drawer list. */
    private function normalizeDrawerClassList(array $classList): array
    {
        return array_values(array_filter(
            $classList,
            fn (mixed $class): bool => is_string($class) && !in_array($class, ['c-nav__item--tiles'], true)
        ));
    }

    /** Build a stable copied ID for appended drawer items. */
    private function getDrawerItemId(array $item): string
    {
        return 'drawer-appended-' . ($item['id'] ?? md5((string) $item['href']));
    }
}
