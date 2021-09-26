<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class QuickEntrySubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'quick_entries';
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('view_own_timesheet')) {
            $event->addBack($this->path('timesheet'));
        }

        $event->addHelp($this->documentationLink('quick_entries.html'));
    }
}
