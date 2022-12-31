<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

final class TimesheetsSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'timesheets';
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('create_own_timesheet')) {
            $event->addCreate($this->path('timesheet_create'));
        }

        if ($this->isGranted('export_own_timesheet')) {
            $event->addAction('download', ['url' => $this->path('timesheet_export'), 'class' => 'modal-ajax-form', 'title' => 'export']);
        }
    }
}
