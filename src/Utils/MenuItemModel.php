<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use KevinPapst\AdminLTEBundle\Model\MenuItemModel as BaseMenuItemModel;

class MenuItemModel extends BaseMenuItemModel
{
    private $childRoutes = [];

    public function setChildRoutes(array $routes): MenuItemModel
    {
        $this->childRoutes = $routes;

        return $this;
    }

    public function addChildRoute(string $route): MenuItemModel
    {
        $this->childRoutes[] = $route;

        return $this;
    }

    public function isChildRoute(string $route): bool
    {
        return \in_array($route, $this->childRoutes);
    }
}
