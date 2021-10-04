<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class TimesheetsViewsSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'timesheets_views';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addAction('timesheet', ['url' => $this->path('timesheet'), 'title' => 'menu.timesheet']);
        $event->addAction('calendar', ['url' => $this->path('calendar'), 'title' => 'calendar.title']);
    }
}
