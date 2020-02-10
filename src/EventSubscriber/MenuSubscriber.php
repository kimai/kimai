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
use App\Utils\MenuItemModel;
use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemInterface;
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

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMainMenuConfigure', 100],
        ];
    }

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

    private function configureMainMenu(SidebarMenuEvent $menu)
    {
        $auth = $this->security;

        if ($auth->isGranted('view_own_timesheet')) {
            $timesheets = new MenuItemModel('timesheet', 'menu.timesheet', 'timesheet', [], $this->getIcon('timesheet'));
            $timesheets->setChildRoutes(['timesheet_export', 'timesheet_edit', 'timesheet_create', 'timesheet_multi_update']);
            $menu->addItem($timesheets);
            $menu->addItem(
                new MenuItemModel('calendar', 'calendar.title', 'calendar', [], $this->getIcon('calendar'))
            );
        }

        if ($auth->isGranted('view_invoice')) {
            $invoice = new MenuItemModel('invoice', 'menu.invoice', 'invoice', [], $this->getIcon('invoice'));
            $invoice->setChildRoutes(['admin_invoice_template', 'admin_invoice_template_edit', 'admin_invoice_template_create', 'admin_invoice_template_copy']);
            $menu->addItem($invoice);
        }

        if ($auth->isGranted('create_export')) {
            $menu->addItem(
                new MenuItemModel('export', 'menu.export', 'export', [], $this->getIcon('export'))
            );
        }
    }

    private function configureAdminMenu(MenuItemInterface $menu)
    {
        $auth = $this->security;

        if ($auth->isGranted('view_other_timesheet')) {
            $timesheets = new MenuItemModel('timesheet_admin', 'menu.admin_timesheet', 'admin_timesheet', [], $this->getIcon('timesheet-team'));
            $timesheets->setChildRoutes(['admin_timesheet_export', 'admin_timesheet_edit', 'admin_timesheet_create', 'admin_timesheet_multi_update']);
            $menu->addChild($timesheets);
        }

        if ($auth->isGranted('view_customer') || $auth->isGranted('view_teamlead_customer') || $auth->isGranted('view_team_customer')) {
            $customers = new MenuItemModel('customer_admin', 'menu.admin_customer', 'admin_customer', [], $this->getIcon('customer'));
            $customers->setChildRoutes(['admin_customer_create', 'admin_customer_permissions', 'customer_details', 'admin_customer_edit', 'admin_customer_delete']);
            $menu->addChild($customers);
        }

        if ($auth->isGranted('view_project') || $auth->isGranted('view_teamlead_project') || $auth->isGranted('view_team_project')) {
            $projects = new MenuItemModel('project_admin', 'menu.admin_project', 'admin_project', [], $this->getIcon('project'));
            $projects->setChildRoutes(['admin_project_permissions', 'admin_project_create', 'project_details', 'admin_project_edit', 'admin_project_delete']);
            $menu->addChild($projects);
        }

        if ($auth->isGranted('view_activity')) {
            $activities = new MenuItemModel('activity_admin', 'menu.admin_activity', 'admin_activity', [], $this->getIcon('activity'));
            $activities->setChildRoutes(['admin_activity_create', 'activity_details', 'admin_activity_edit', 'admin_activity_delete']);
            $menu->addChild($activities);
        }

        if ($auth->isGranted('view_tag')) {
            $menu->addChild(
                new MenuItemModel('tags', 'menu.tags', 'tags', [], 'fas fa-tags')
            );
        }
    }

    private function configureSystemMenu(MenuItemInterface $menu)
    {
        $auth = $this->security;

        if ($auth->isGranted('view_user')) {
            $users = new MenuItemModel('user_admin', 'menu.admin_user', 'admin_user', [], $this->getIcon('user'));
            $users->setChildRoutes(['admin_user_create', 'admin_user_delete',  'admin_user_permissions', 'user_profile', 'user_profile_edit', 'user_profile_password', 'user_profile_api_token', 'user_profile_roles', 'user_profile_teams', 'user_profile_preferences']);
            $menu->addChild($users);
        }

        if ($auth->isGranted('view_team')) {
            $teams = new MenuItemModel('user_team', 'menu.admin_team', 'admin_team', [], $this->getIcon('team'));
            $teams->setChildRoutes(['admin_team_create', 'admin_team_edit']);
            $menu->addChild($teams);
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
