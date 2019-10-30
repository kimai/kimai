<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Twig\IconExtension;
use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Menu event subscriber is creating the Kimai default menu structure.
 */
final class MenuSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $security;
    /**
     * @var IconExtension
     */
    private $icons;

    public function __construct(AuthorizationCheckerInterface $security)
    {
        $this->security = $security;
        $this->icons = new IconExtension();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMainMenuConfigure', 100],
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

        $this->configureMainMenu($event->getMenu());
        $this->configureAdminMenu($event->getAdminMenu());
        $this->configureSystemMenu($event->getSystemMenu());
    }

    /**
     * @param SidebarMenuEvent $menu
     */
    private function configureMainMenu(SidebarMenuEvent $menu)
    {
        $auth = $this->security;

        if ($auth->isGranted('view_own_timesheet')) {
            $menu->addItem(
                new MenuItemModel('timesheet', 'menu.timesheet', 'timesheet', [], $this->getIcon('timesheet'))
            );
            $menu->addItem(
                new MenuItemModel('calendar', 'calendar.title', 'calendar', [], $this->getIcon('calendar'))
            );
        }

        if ($auth->isGranted('view_invoice')) {
            $menu->addItem(
                new MenuItemModel('invoice', 'menu.invoice', 'invoice', [], $this->getIcon('invoice'))
            );
        }

        if ($auth->isGranted('create_export')) {
            $menu->addItem(
                new MenuItemModel('export', 'menu.export', 'export', [], $this->getIcon('export'))
            );
        }
    }

    /**
     * @param MenuItemModel $menu
     */
    private function configureAdminMenu(MenuItemModel $menu)
    {
        $auth = $this->security;

        if ($auth->isGranted('view_other_timesheet')) {
            $menu->addChild(
                new MenuItemModel('timesheet_admin', 'menu.admin_timesheet', 'admin_timesheet', [], $this->getIcon('timesheet-team'))
            );
        }

        if ($auth->isGranted('view_customer')) {
            $menu->addChild(
                new MenuItemModel('customer_admin', 'menu.admin_customer', 'admin_customer', [], $this->getIcon('customer'))
            );
        }

        if ($auth->isGranted('view_project')) {
            $menu->addChild(
                new MenuItemModel('project_admin', 'menu.admin_project', 'admin_project', [], $this->getIcon('project'))
            );
        }

        if ($auth->isGranted('view_activity')) {
            $menu->addChild(
                new MenuItemModel('activity_admin', 'menu.admin_activity', 'admin_activity', [], $this->getIcon('activity'))
            );
        }

        if ($auth->isGranted('view_tag')) {
            $menu->addChild(
                new MenuItemModel('tags', 'menu.tags', 'tags', [], 'fas fa-tags')
            );
        }
    }

    /**
     * @param MenuItemModel $menu
     */
    private function configureSystemMenu(MenuItemModel $menu)
    {
        $auth = $this->security;

        if ($auth->isGranted('view_user')) {
            $menu->addChild(
                new MenuItemModel('user_admin', 'menu.admin_user', 'admin_user', [], $this->getIcon('user'))
            );
        }

        if ($auth->isGranted('view_team')) {
            $menu->addChild(
                new MenuItemModel('user_team', 'menu.admin_team', 'admin_team', [], $this->getIcon('team'))
            );
        }

        if ($auth->isGranted('plugins')) {
            $menu->addChild(
                new MenuItemModel('plugins', 'menu.plugin', 'plugins', [], $this->getIcon('plugin'))
            );
        }

        if ($auth->isGranted('system_configuration')) {
            $menu->addChild(
                new MenuItemModel('system_configuration', 'menu.system_configuration', 'system_configuration', [], $this->getIcon('configuration'))
            );
        }

        if ($auth->isGranted('system_information')) {
            $menu->addChild(
                new MenuItemModel('doctor', 'menu.doctor', 'doctor', [], $this->getIcon('doctor'))
            );
        }
    }

    private function getIcon(string $icon)
    {
        return $this->icons->icon($icon, $icon);
    }
}
