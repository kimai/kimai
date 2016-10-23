<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\EventListener;

use AppBundle\Event\ConfigureMainMenuEvent;
use AppBundle\Event\ConfigureAdminMenuEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Avanzu\AdminThemeBundle\Model\MenuItemModel;
use Avanzu\AdminThemeBundle\Event\SidebarMenuEvent;

/**
 * Menus for timesheet
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class Menu
{
    /**
     * @param \AppBundle\Event\ConfigureMainMenuEvent $event
     */
    public function onMainMenuConfigure(ConfigureMainMenuEvent $event)
    {
        $auth = $event->getAuth();

        $isLoggedIn = $auth->isGranted('IS_AUTHENTICATED_FULLY');
        $isUser = $isLoggedIn && $auth->isGranted('ROLE_USER');

        if (!$isLoggedIn || !$isUser) {
            return;
        }

        $menu = $event->getMenu();
        $menu->addItem(
            new MenuItemModel('timesheet', 'menu.timesheet', 'timesheet', [], 'fa fa-clock-o')
        );
    }

    /**
     * @param \AppBundle\Event\ConfigureAdminMenuEvent $event
     */
    public function onAdminMenuConfigure(ConfigureAdminMenuEvent $event)
    {
        $menu = $event->getAdminMenu();
        $auth = $event->getAuth();

        $isLoggedIn = $auth->isGranted('IS_AUTHENTICATED_FULLY');
        $isAdmin = $isLoggedIn && $auth->isGranted('ROLE_ADMIN');

        if (!$isAdmin) {
            return;
        }

        $menu->addChild(
            new MenuItemModel('timesheet_admin', 'menu.admin_timesheet', 'admin_timesheet', [], 'fa fa-clock-o')
        )->addChild(
            new MenuItemModel('project_admin', 'menu.admin_project', 'admin_project', [], 'fa fa-object-group')
        )->addChild(
            new MenuItemModel('activity_admin', 'menu.admin_activity', 'admin_activity', [], 'fa fa-tasks')
        )
        ;
    }
}