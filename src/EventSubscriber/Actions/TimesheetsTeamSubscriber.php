<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Repository\Query\TimesheetQuery;

class TimesheetsTeamSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'timesheets_team';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var TimesheetQuery $query */
        $query = $payload['query'];

        $event->addSearchToggle($query);
        $event->addColumnToggle('#modal_timesheet_admin');

        if ($this->isGranted('export_other_timesheet')) {
            $event->addAction('download', ['url' => $this->path('admin_timesheet_export'), 'class' => 'toolbar-action modal-ajax-form']);
        }

        if ($this->isGranted('create_other_timesheet')) {
            $event->addActionToSubmenu('create', 'single', ['title' => 'create', 'url' => $this->path('admin_timesheet_create'), 'class' => 'create-ts modal-ajax-form']);
            $event->addActionToSubmenu('create', 'multi-user', ['title' => 'create-timesheet-multiuser', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet_create_multiuser'), 'class' => 'create-ts-mu modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('timesheet.html'));
    }
}
