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

class TimesheetsSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'timesheets';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var TimesheetQuery $query */
        $query = $payload['query'];

        $event->addSearchToggle($query);
        $event->addColumnToggle('#modal_timesheet');

        if ($this->isGranted('export_own_timesheet')) {
            $event->addAction('download', ['url' => $this->path('timesheet_export'), 'class' => 'toolbar-action modal-ajax-form']);
        }

        if ($this->isGranted('create_own_timesheet')) {
            $event->addCreate($this->path('timesheet_create'));
        }

        $event->addHelp($this->documentationLink('timesheet.html'));
    }
}
