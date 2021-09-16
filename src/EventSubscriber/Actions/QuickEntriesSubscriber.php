<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class QuickEntriesSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'quick-entries';
    }

    public function onActions(PageActionsEvent $event): void
    {
        // FIXME
        $event->addHelp($this->documentationLink('teams.html'));
    }
}
