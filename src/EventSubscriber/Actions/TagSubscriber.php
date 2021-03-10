<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\Tag;
use App\Event\PageActionsEvent;

class TagSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.tag' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $payload = $event->getPayload();

        if (!isset($payload['tag'])) {
            return;
        }

        // tag is an array[id, name, color, amount]
        $tag = $payload['tag'];
        $view = $payload['view'];

        $id = null;
        $name = null;

        if (\is_array($tag)) {
            $id = $tag['id'];
            $name = $tag['name'];
        } elseif ($tag instanceof Tag) {
            $id = $tag->getId();
            $name = $tag->getName();
        }

        if ($id === null) {
            return;
        }

        $actions = $event->getActions();

        if ($this->isGranted('manage_tag')) {
            $class = ($view === 'edit' ? '' : 'modal-ajax-form');
            $actions['edit'] = ['url' => $this->path('tags_edit', ['id' => $id]), 'class' => $class];
        }

        $filters = [];

        if ($this->isGranted('view_other_timesheet')) {
            $filters['timesheet'] = ['title' => 'timesheet', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet', ['tags' => $name])];
        }

        if (\count($filters) > 0) {
            $actions['filter'] = ['children' => $filters];
        }

        if ($view === 'index' && $this->isGranted('delete_tag')) {
            $actions['trash'] = [
                'url' => $this->path('delete_tag', ['id' => $id]),
                'class' => 'api-link',
                'attr' => [
                    'data-event' => 'kimai.tagDelete kimai.tagUpdate',
                    'data-method' => 'DELETE',
                    'data-question' => 'confirm.delete',
                    'data-msg-error' => 'action.delete.error',
                    'data-msg-success' => 'action.delete.success'
                ]
            ];
        }

        $event->setActions($actions);
    }
}
