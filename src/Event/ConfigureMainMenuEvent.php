<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Utils\MenuItemModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The ConfigureMainMenuEvent is used for populating the main navigation.
 */
final class ConfigureMainMenuEvent extends Event
{
    private $request;
    private $menu;
    private $admin;
    private $system;

    public function __construct(Request $request, MenuItemModel $mainMenu, MenuItemModel $admin, MenuItemModel $system)
    {
        $this->request = $request;
        $this->menu = $mainMenu;
        $this->admin = $admin;
        $this->system = $system;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getMenu(): MenuItemModel
    {
        return $this->menu;
    }

    public function getAdminMenu(): MenuItemModel
    {
        return $this->admin;
    }

    public function getSystemMenu(): MenuItemModel
    {
        return $this->system;
    }
}
