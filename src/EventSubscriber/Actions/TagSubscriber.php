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

        if (!\is_array($payload) || !\array_key_exists('tag', $payload)) {
            return;
        }

        $tag = $payload['tag'];
        $id = null;
        $name = null;

        if (\is_array($tag)) {
            // tag can be either and array (on index page => [id, name, color, amount]) ...
            $id = $tag['id'];
            $name = $tag['name'];
        } elseif ($tag instanceof Tag) {
            // ...or an entity on detail page
            $id = $tag->getId();
            $name = $tag->getName();
        }

        if (!$event->isIndexView() && $this->isGranted('view_tag')) {
            $event->addAction('back', ['url' => $this->path('tags')]);
        }

        if ($id === null) {
            return;
        }

        if ($this->isGranted('manage_tag')) {
            $class = ($event->isView('edit') ? '' : 'modal-ajax-form');
            $event->addAction('edit', ['url' => $this->path('tags_edit', ['id' => $id]), 'class' => $class]);
        }

        if ($this->isGranted('view_other_timesheet')) {
            $event->addActionToSubmenu('filter', 'timesheet', ['title' => 'timesheet', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet', ['tags' => $name])]);
        }

        if ($event->isIndexView() && $this->isGranted('delete_tag')) {
            $event->addAction('trash', [
                'url' => $this->path('delete_tag', ['id' => $id]),
                'class' => 'api-link',
                'attr' => [
                    'data-event' => 'kimai.tagDelete kimai.tagUpdate',
                    'data-method' => 'DELETE',
                    'data-question' => 'confirm.delete',
                    'data-msg-error' => 'action.delete.error',
                    'data-msg-success' => 'action.delete.success'
                ]
            ]);
        }
    }
}
