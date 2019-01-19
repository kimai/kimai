<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ConfigureAdminMenuEvent;
use App\Event\ConfigureMainMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Menu event subscriber for timesheet, customer, projects, activities.
 * This is a sample implementation for developer who want to add new navigation entries in their bundles.
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

        if (!$auth->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        $menu = $event->getMenu();

        if ($auth->isGranted('view_own_timesheet')) {
            $menu->addItem(
                new MenuItemModel('timesheet', 'menu.timesheet', 'timesheet', [], 'far fa-clock')
            );
        }

        if ($auth->isGranted('view_invoice')) {
            $menu->addItem(
                new MenuItemModel('invoice', 'menu.invoice', 'invoice', [], 'fas fa-file-invoice')
            );
        }
    }

    /**
     * @param \App\Event\ConfigureAdminMenuEvent $event
     */
    public function onAdminMenuConfigure(ConfigureAdminMenuEvent $event)
    {
        $auth = $this->security;

        if (!$auth->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        $menu = $event->getAdminMenu();

        if ($auth->isGranted('view_other_timesheet')) {
            $menu->addChild(
                new MenuItemModel('timesheet_admin', 'menu.admin_timesheet', 'admin_timesheet', [], 'far fa-clock')
            );
        }

        if ($auth->isGranted('view_user')) {
            $menu->addChild(
                new MenuItemModel('user_admin', 'menu.admin_user', 'admin_user', [], 'fas fa-user')
            );
        }

        if ($auth->isGranted('view_customer')) {
            $menu->addChild(
                new MenuItemModel('customer_admin', 'menu.admin_customer', 'admin_customer', [], 'fas fa-users')
            );
        }

        if ($auth->isGranted('view_project')) {
            $menu->addChild(
                new MenuItemModel('project_admin', 'menu.admin_project', 'admin_project', [], 'fas fa-project-diagram')
            );
        }

        if ($auth->isGranted('view_activity')) {
            $menu->addChild(
                new MenuItemModel('activity_admin', 'menu.admin_activity', 'admin_activity', [], 'fas fa-tasks')
            );
        }
    }
}
