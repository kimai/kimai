<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class CalendarSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.calendar' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        if ($this->isGranted('create_own_timesheet')) {
            $event->addCreate($this->path('timesheet_create'));
        }

        $event->addHelp($this->documentationLink('calendar.html'));
    }
}
