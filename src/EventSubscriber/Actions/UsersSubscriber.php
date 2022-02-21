<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Repository\Query\UserQuery;

class UsersSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'users';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var UserQuery $query */
        $query = $payload['query'];

        $event->addSearchToggle($query);

        $event->addQuickExport($this->path('user_export'));

        if ($this->isGranted('create_user')) {
            $event->addCreate($this->path('admin_user_create'), false);
        }

        if ($this->isGranted('view_reporting') && $this->isGranted('view_other_reporting') && $this->isGranted('view_other_timesheet')) {
            $event->addActionToSubmenu('report', 'weekly', ['url' => $this->path('report_weekly_users'), 'translation_domain' => 'reporting', 'title' => 'report_weekly_users']);
            $event->addActionToSubmenu('report', 'monthly', ['url' => $this->path('report_monthly_users'), 'translation_domain' => 'reporting', 'title' => 'report_monthly_users']);
            $event->addActionToSubmenu('report', 'yearly', ['url' => $this->path('report_yearly_users'), 'translation_domain' => 'reporting', 'title' => 'report_yearly_users']);
        }

        if ($this->isGranted('system_configuration')) {
            $event->addAction('settings', ['title' => 'settings', 'translation_domain' => 'actions', 'url' => $this->path('system_configuration_section', ['section' => 'user']), 'class' => 'modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('users.html'));
    }
}
