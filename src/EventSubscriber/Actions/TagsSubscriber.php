<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class TagsSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.tags' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $payload = $event->getPayload();

        if (!isset($payload['view'])) {
            return;
        }

        $actions = $event->getActions();

        $actions['search'] = ['class' => 'search-toggle visible-xs-inline'];

        if ($this->isGranted('manage_tag')) {
            $actions['create'] = ['url' => $this->path('tags_create'), 'class' => 'modal-ajax-form'];
        }

        $actions['help'] = ['url' => $this->documentationLink('tags.html'), 'target' => '_blank'];

        $event->setActions($actions);
    }
}
