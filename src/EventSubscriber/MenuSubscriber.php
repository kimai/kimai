<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Event\ConfigureAdminMenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Avanzu\AdminThemeBundle\Model\MenuItemModel;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Menu event subscriber for timesheet, customer, projects, activities.
 * This is a sample implementation for developer who want to add new navigation entries in their bundles.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class MenuSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $security;

    /**
     * MenuSubscriber constructor.
     * @param AuthorizationCheckerInterface $security
     */
    public function __construct(AuthorizationCheckerInterface $security)
    {
        $this->security = $security;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::CONFIGURE => ['onMainMenuConfigure', 100],
            ConfigureAdminMenuEvent::CONFIGURE => ['onAdminMenuConfigure', 100],
        ];
    }

    /**
     * @param \App\Event\ConfigureMainMenuEvent $event
     */
    public function onMainMenuConfigure(ConfigureMainMenuEvent $event)
    {
        $auth = $this->security;

        $isLoggedIn = $auth->isGranted('IS_AUTHENTICATED_REMEMBERED');
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
     * @param \App\Event\ConfigureAdminMenuEvent $event
     */
    public function onAdminMenuConfigure(ConfigureAdminMenuEvent $event)
    {
        $menu = $event->getAdminMenu();
        $auth = $this->security;

        if (!$auth->isGranted('IS_AUTHENTICATED_REMEMBERED') || !$auth->isGranted('ROLE_TEAMLEAD')) {
            return;
        }

        $menu->addChild(
            new MenuItemModel('timesheet_admin', 'menu.admin_timesheet', 'admin_timesheet', [], 'fa fa-clock-o')
        );

        if (!$auth->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($auth->isGranted('ROLE_SUPER_ADMIN')) {
            $menu->addChild(
                new MenuItemModel('user_admin', 'menu.admin_user', 'admin_user', [], 'fa fa-user')
            );
        }

        $menu->addChild(
            new MenuItemModel('customer_admin', 'menu.admin_customer', 'admin_customer', [], 'fa fa-users')
        )->addChild(
            new MenuItemModel('project_admin', 'menu.admin_project', 'admin_project', [], 'fa fa-book')
        )->addChild(
            new MenuItemModel('activity_admin', 'menu.admin_activity', 'admin_activity', [], 'fa fa-tasks')
        );
    }
}
