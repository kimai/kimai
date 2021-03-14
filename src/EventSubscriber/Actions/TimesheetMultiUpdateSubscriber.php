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
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.timesheets_multi_update' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $event->addBack($this->path('timesheet'));
        $event->addHelp($this->documentationLink('timesheet.html'));
    }
}
