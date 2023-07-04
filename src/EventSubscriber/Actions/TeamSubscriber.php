<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\Team;
use App\Event\PageActionsEvent;

final class TeamSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'team';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var Team $team */
        $team = $payload['team'];

        if ($team->getId() === null) {
            return;
        }

        if ($this->isGranted('edit', $team)) {
            if (!$event->isView('edit')) {
                $event->addEdit($this->path('admin_team_edit', ['id' => $team->getId()]));
            }

            if ($this->isGranted('create_team')) {
                $event->addAction('copy', ['url' => $this->path('team_duplicate', ['id' => $team->getId()]), 'title' => 'copy', 'translation_domain' => 'actions', 'class' => 'modal-ajax-form']);
            }
        }

        if ($event->isIndexView() && $this->isGranted('delete', $team)) {
            $event->addAction('trash', [
                'url' => $this->path('delete_team', ['id' => $team->getId()]),
                'class' => 'api-link text-red',
                'translation_domain' => 'actions',
                'attr' => [
                    'data-event' => 'kimai.teamDelete kimai.teamUpdate',
                    'data-method' => 'DELETE',
                    'data-question' => 'confirm.delete',
                    'data-msg-error' => 'action.delete.error',
                    'data-msg-success' => 'action.delete.success'
                ]
            ]);
        }
    }
}
