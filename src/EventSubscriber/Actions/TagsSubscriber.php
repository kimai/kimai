<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

final class TagsSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'tags';
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('manage_tag') || $this->isGranted('create_tag')) {
            $event->addCreate($this->path('tags_create'));
        }
    }
}
