<?php

declare(strict_types=1);

namespace LidingoCustomisation\Navigation;

use Municipio\Helper\Navigation;

class DrawerMenuAppend
{
    private const MOBILE_IDENTIFIER = 'mobile';
    private const PRIMARY_IDENTIFIER = 'primary';
    private const LANGUAGE_IDENTIFIER = 'language';
    private const PRIMARY_MENU_NAME = 'main-menu';
    private const LANGUAGE_MENU_NAME = 'language-menu';

    /** Register drawer menu augmentation hooks. */
    public function addHooks(): void
    {
        add_filter('Municipio/Navigation/Items', [$this, 'appendMenusToDrawer'], 20, 2);
    }

    /** Append primary and language menu items at the bottom of the main drawer menu. */
    public function appendMenusToDrawer(array $items, string $identifier): array
    {
        if ($identifier !== self::MOBILE_IDENTIFIER) {
            return $items;
        }

        $appendedItems = array_merge(
            $this->getMenuItems(self::PRIMARY_IDENTIFIER, self::PRIMARY_MENU_NAME),
            $this->getMenuItems(self::LANGUAGE_IDENTIFIER, self::LANGUAGE_MENU_NAME)
        );

        if (empty($appendedItems)) {
            return $items;
        }

        return array_values(array_merge($items, $appendedItems));
    }

    /** Resolve a top-level menu as Municipio-shaped nested items. */
    private function getMenuItems(string $identifier, string $menuName): array
    {
        $navigation = new Navigation($identifier);
        $items = $navigation->getMenuItems($menuName, null, false, true, true);

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_map(
            static function (array $item): array {
                $item['children'] = false;
                $item['post_parent'] = 0;
                $item['style'] = 'default';

                return $item;
            },
            array_filter($items, static fn (mixed $item): bool => is_array($item))
        ));
    }
}
