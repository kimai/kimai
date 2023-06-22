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

final class TagSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'tag';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

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

        if ($id === null) {
            return;
        }

        if ($this->isGranted('manage_tag')) {
            $event->addEdit($this->path('tags_edit', ['id' => $id]));
        }

        if ($this->isGranted('view_other_timesheet')) {
            $event->addActionToSubmenu('filter', 'timesheet', ['title' => 'timesheet.filter', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet', ['tags' => $name])]);
        }

        if ($event->isIndexView() && $this->isGranted('delete_tag')) {
            $event->addAction('trash', [
                'url' => $this->path('delete_tag', ['id' => $id]),
                'class' => 'api-link text-red',
                'translation_domain' => 'actions',
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
