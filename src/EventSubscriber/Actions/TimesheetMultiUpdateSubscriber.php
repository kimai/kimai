<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class TimesheetMultiUpdateSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'timesheets_multi_update';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addBack($this->path('timesheet'));
        $event->addHelp($this->documentationLink('timesheet.html'));
    }
}
