<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class TimesheetsSubscriber extends AbstractTimesheetsSubscriber
{
    public static function getActionName(): string
    {
        return 'timesheets';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addSearchToggle();
        $event->addColumnToggle('#modal_timesheet');

        if ($this->isGranted('export_own_timesheet')) {
            $this->addExporter($event, 'timesheet_export');
        }

        if ($this->isGranted('create_own_timesheet')) {
            $event->addCreate($this->path('timesheet_create'));
        }

        $event->addHelp($this->documentationLink('timesheet.html'));
    }
}
