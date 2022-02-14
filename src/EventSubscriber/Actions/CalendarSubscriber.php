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
    public static function getActionName(): string
    {
        return 'calendar';
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('create_own_timesheet')) {
            $event->addCreate($this->path('timesheet_create'));
        }

        if ($this->isGranted('system_configuration')) {
            $event->addAction('settings', ['url' => $this->path('system_configuration_section', ['section' => 'calendar']), 'class' => 'modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('calendar.html'));
    }
}
