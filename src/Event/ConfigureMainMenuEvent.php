<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Utils\MenuItemModel;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The ConfigureMainMenuEvent is used for populating the main navigation.
 */
final class ConfigureMainMenuEvent extends Event
{
    private MenuItemModel $menu;
    private MenuItemModel $apps;
    private MenuItemModel $admin;
    private MenuItemModel $system;

    public function __construct()
    {
        $this->menu = new MenuItemModel('main', 'menu.root');
        $this->apps = new MenuItemModel('apps', 'menu.apps', '', [], 'applications');
        $this->admin = new MenuItemModel('admin', 'menu.admin', '', [], 'administration');
        $this->system = new MenuItemModel('system', 'menu.system', '', [], 'configuration');
    }

    public function findById(string $identifier): ?MenuItemModel
    {
        if (($tmp = $this->menu->findChild($identifier)) !== null) {
            return $tmp;
        }

        if (($tmp = $this->apps->findChild($identifier)) !== null) {
            return $tmp;
        }

        if (($tmp = $this->admin->findChild($identifier)) !== null) {
            return $tmp;
        }

        if (($tmp = $this->system->findChild($identifier)) !== null) {
            return $tmp;
        }

        return null;
    }

    public function getMenu(): MenuItemModel
    {
        return $this->menu;
    }

    public function getTimesheetMenu(): ?MenuItemModel
    {
        return $this->menu->getChild('times');
    }

    public function getInvoiceMenu(): ?MenuItemModel
    {
        return $this->menu->getChild('invoice');
    }

    public function getReportingMenu(): ?MenuItemModel
    {
        return $this->menu->getChild('reporting');
    }

    public function getAppsMenu(): MenuItemModel
    {
        return $this->apps;
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
