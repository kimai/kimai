<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class ProjectsSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.projects' => ['onActions', 1000],
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
        $actions['visibility'] = ['modal' => '#modal_project_admin'];
        $actions['download'] = ['url' => $this->path('project_export'), 'class' => 'toolbar-action'];

        if ($this->isGranted('create_project')) {
            $actions['create'] = ['url' => $this->path('admin_project_create'), 'class' => 'modal-ajax-form'];
        }

        $actions['help'] = ['url' => $this->documentationLink('project.html'), 'target' => '_blank'];

        $event->setActions($actions);
    }
}
