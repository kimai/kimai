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
        return 'weekly_times';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addHelp($this->documentationLink('weekly-times.html'));
    }
}
