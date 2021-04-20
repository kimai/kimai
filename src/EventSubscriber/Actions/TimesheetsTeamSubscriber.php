<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class TimesheetsTeamSubscriber extends AbstractTimesheetsSubscriber
{
    public static function getActionName(): string
    {
        return 'timesheets_team';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addSearchToggle();
        $event->addColumnToggle('#modal_timesheet_admin');

        if ($this->isGranted('export_other_timesheet')) {
            $this->addExporter($event, 'admin_timesheet_export');
        }

        if ($this->isGranted('create_other_timesheet')) {
            $event->addActionToSubmenu('create', 'single', ['title' => 'create', 'url' => $this->path('admin_timesheet_create'), 'class' => 'create-ts modal-ajax-form']);
            $event->addActionToSubmenu('create', 'multi-user', ['title' => 'create-timesheet-multiuser', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet_create_multiuser'), 'class' => 'create-ts-mu modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('timesheet.html'));
    }
}
