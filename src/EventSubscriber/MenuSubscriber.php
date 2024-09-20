<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use KevinPapst\TablerBundle\Helper\ContextHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ContextHelper $helper
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMainMenuConfigure', 100],
        ];
    }

    private function addDivider(MenuItemModel $menu): void
    {
        if ($this->helper->isBoxedLayout()) {
            $menu->addChild(MenuItemModel::createDivider());
        }
    }

    public function onMainMenuConfigure(ConfigureMainMenuEvent $event): void
    {
        $auth = $this->security;

        if (!$auth->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        // main menu
        $menu = $event->getMenu();
        /** @var User $user */
        $user = $auth->getUser();

        $menu->addChild(new MenuItemModel('dashboard', 'dashboard.title', 'dashboard', [], 'dashboard'));
        $menu->addChild(new MenuItemModel('favorites', 'favorite_routes', null, [], 'bookmarked'));

        // ------------------- timesheet menu -------------------
        $times = new MenuItemModel('times', 'time_tracking', null, [], 'timesheet');

        if ($auth->isGranted('view_own_timesheet')) {
            $timesheets = new MenuItemModel('timesheet', 'my_times', 'timesheet', [], 'timesheet');
            $timesheets->setChildRoutes(['timesheet_export', 'timesheet_edit', 'timesheet_create', 'timesheet_multi_update']);
            $times->addChild($timesheets);

            if ($auth->isGranted('quick-entry')) {
                $times->addChild(
                    new MenuItemModel('quick_entry', 'quick_entry.title', 'quick_entry', [], 'weekly-times')
                );
            }

            $times->addChild(
                new MenuItemModel('calendar', 'calendar', 'calendar', [], 'calendar')
            );
        }

        $this->addDivider($times);

        if ($auth->isGranted('create_export')) {
            $times->addChild(
                new MenuItemModel('export', 'export', 'export', [], 'export')
            );
        }

        if ($auth->isGranted('view_other_timesheet')) {
            $timesheets = new MenuItemModel('timesheet_admin', 'all_times', 'admin_timesheet', [], 'timesheet-team');
            $timesheets->setChildRoutes(['admin_timesheet_export', 'admin_timesheet_edit', 'admin_timesheet_create', 'admin_timesheet_multi_update']);
            $times->addChild($timesheets);
        }

        if ($times->hasChildren()) {
            $times->setExpanded(true); // Kimai is all about time-tracking, so we expand this menu always
            $menu->addChild($times);
        }

        $contract = new MenuItemModel('contract', 'work_contract', null, [], 'contract');
        if ($auth->isGranted('hours', $user)) {
            $contract->addChild(new MenuItemModel('contract_status', 'work_times', 'user_contract', [], 'work_times'));
        }

        if ($contract->hasChildren()) {
            $menu->addChild($contract);
        }

        if ($auth->isGranted('view_reporting')) {
            $reporting = new MenuItemModel('reporting', 'menu.reporting', 'reporting', [], 'reporting');
            $reporting->setChildRoutes(['report_user_week', 'report_user_month', 'report_weekly_users', 'report_monthly_users', 'report_project_view']);
            $menu->addChild($reporting);
        }

        // ------------------- invoice menu -------------------
        $invoice = new MenuItemModel('invoices', 'invoices', null, [], 'invoice');

        if ($auth->isGranted('create_invoice')) {
            $invoice->addChild(new MenuItemModel('invoice', 'invoice_form.title', 'invoice', [], 'invoice'));
        }

        if ($auth->isGranted('view_invoice')) {
            $tmpMenu = new MenuItemModel('invoice_listing', 'all_invoices', 'admin_invoice_list', [], 'list');
            $tmpMenu->setChildRoutes(['admin_invoice_edit']);
            $invoice->addChild($tmpMenu);
        }

        if ($auth->isGranted('manage_invoice_template')) {
            $tmpMenu = new MenuItemModel('invoice-template', 'admin_invoice_template.title', 'admin_invoice_template', [], 'invoice-template');
            $tmpMenu->setChildRoutes(['admin_invoice_template_edit', 'admin_invoice_template_create', 'admin_invoice_template_copy', 'admin_invoice_document_upload']);
            $invoice->addChild($tmpMenu);
        }

        if ($invoice->hasChildren()) {
            $this->addDivider($invoice);
        }

        $menu->addChild($invoice);

        // ------------------- admin menu -------------------
        $menu = $event->getAdminMenu();

        if ($auth->isGranted('view_customer') || $auth->isGranted('view_teamlead_customer') || $auth->isGranted('view_team_customer')) {
            $customers = new MenuItemModel('customers', 'customers', 'admin_customer', [], 'customer');
            $customers->setChildRoutes(['admin_customer_create', 'admin_customer_permissions', 'customer_details', 'admin_customer_edit', 'admin_customer_delete']);
            $menu->addChild($customers);
        }

        if ($auth->isGranted('view_project') || $auth->isGranted('view_teamlead_project') || $auth->isGranted('view_team_project')) {
            $projects = new MenuItemModel('projects', 'projects', 'admin_project', [], 'project');
            $projects->setChildRoutes(['admin_project_permissions', 'admin_project_create', 'project_details', 'admin_project_edit', 'admin_project_delete']);
            $menu->addChild($projects);
        }

        if ($auth->isGranted('view_activity') || $auth->isGranted('view_teamlead_activity') || $auth->isGranted('view_team_activity')) {
            $activities = new MenuItemModel('activities', 'activities', 'admin_activity', [], 'activity');
            $activities->setChildRoutes(['admin_activity_create', 'activity_details', 'admin_activity_edit', 'admin_activity_delete']);
            $menu->addChild($activities);
        }

        if ($auth->isGranted('view_tag')) {
            $menu->addChild(
                new MenuItemModel('tags', 'tags', 'tags', [], 'fas fa-tags')
            );
        }

        $this->addDivider($menu);

        // ------------------- system menu -------------------
        $menu = $event->getSystemMenu();

        if ($auth->isGranted('view_user')) {
            $users = new MenuItemModel('users', 'users', 'admin_user', [], 'users');
            $users->setChildRoutes(['admin_user_create', 'admin_user_delete',  'user_profile', 'user_profile_edit', 'user_profile_password', 'user_profile_api_token', 'user_profile_roles', 'user_profile_teams', 'user_profile_preferences', 'user_profile_2fa']);
            $menu->addChild($users);
        }

        if ($auth->isGranted('role_permissions')) {
            $users = new MenuItemModel('roles', 'profile.roles', 'admin_user_permissions', [], 'permissions');
            $menu->addChild($users);
        }

        if ($auth->isGranted('view_team')) {
            $teams = new MenuItemModel('teams', 'teams', 'admin_team', [], 'team');
            $teams->setChildRoutes(['admin_team_create', 'admin_team_edit']);
            $menu->addChild($teams);
        }

        if ($menu->hasChildren()) {
            $this->addDivider($menu);
        }

        if ($auth->isGranted('plugins')) {
            $menu->addChild(
                new MenuItemModel('plugins', 'menu.plugin', 'plugins', [], 'plugin')
            );
        }

        if ($auth->isGranted('system_configuration')) {
            $systemConfig = new MenuItemModel('configurations', 'menu.system_configuration', 'system_configuration', [], 'configuration');
            $systemConfig->setChildRoutes(['system_configuration_update', 'system_configuration_section']);
            $menu->addChild($systemConfig);
        }

        if ($auth->isGranted('system_information')) {
            $menu->addChild(
                new MenuItemModel('doctor', 'Doctor', 'doctor', [], 'doctor')
            );
        }

        $this->addDivider($menu);
    }
}
