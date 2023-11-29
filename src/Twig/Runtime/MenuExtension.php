<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Utils\MenuItemModel;
use App\Utils\MenuService;
use Twig\Extension\RuntimeExtensionInterface;

final class MenuExtension implements RuntimeExtensionInterface
{
    public function __construct(private MenuService $menuService)
    {
    }

    /**
     * @return array<MenuItemModel>
     */
    public function getUserShortcuts(User $user): array
    {
        $shortcuts = $user->getPreferenceValue('favorite_routes');
        if (!\is_string($shortcuts) || trim($shortcuts) === '') {
            return [];
        }

        $favMenu = [];

        $shortcuts = explode(',', $shortcuts);
        $menu = $this->menuService->getKimaiMenu();
        foreach ($shortcuts as $fav) {
            $tmp = $menu->findById($fav);
            if ($tmp !== null && !$tmp->hasChildren()) {
                $favMenu[] = clone $tmp;
            }
        }

        return $favMenu;
    }
}
