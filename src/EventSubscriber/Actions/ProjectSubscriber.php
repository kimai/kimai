<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\Project;
use App\Event\PageActionsEvent;

class ProjectSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.project' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $payload = $event->getPayload();

        if (!isset($payload['project'])) {
            return;
        }

        /** @var Project $project */
        $project = $payload['project'];
        $view = $payload['view'];

        if ($project->getId() === null) {
            return;
        }

        $actions = $event->getActions();

        if ($this->isGranted('view', $project)) {
            $actions['details'] = ['url' => $this->path('project_details', ['id' => $project->getId()])];
        }

        if ($this->isGranted('edit', $project)) {
            $class = ($view === 'edit' ? '' : 'modal-ajax-form');
            $actions['edit'] = ['url' => $this->path('admin_project_edit', ['id' => $project->getId()]), 'class' => $class];
        }
        if ($this->isGranted('permissions', $project)) {
            $class = ($view === 'permissions' ? '' : 'modal-ajax-form');
            $actions['permissions'] = ['url' => $this->path('admin_project_permissions', ['id' => $project->getId()]), 'class' => $class];
        }

        if (\count($actions) > 0) {
            $actions['divider'] = null;
        }

        if ($project->isVisible() && $project->getCustomer()->isVisible() && $this->isGranted('create_activity')) {
            $actions['create-activity'] = ['icon' => 'create', 'url' => $this->path('admin_activity_create_with_project', ['project' => $project->getId()]), 'class' => 'modal-ajax-form'];
        }

        $filters = [];

        if ($this->isGranted('view_activity')) {
            $filters['activity'] = ['title' => 'activity', 'translation_domain' => 'actions', 'url' => $this->path('admin_activity', ['customers[]' => $project->getCustomer()->getId(), 'projects[]' => $project->getId()])];
        }

        if ($this->isGranted('view_other_timesheet')) {
            $filters['timesheet'] = ['title' => 'timesheet', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet', ['customers[]' => $project->getCustomer()->getId(), 'projects[]' => $project->getId()])];
        }

        if (\count($filters) > 0) {
            $actions['filter'] = ['children' => $filters];
        }

        if ($this->isGranted('edit', $project)) {
            $actions['copy'] = ['url' => $this->path('admin_project_duplicate', ['id' => $project->getId()])];
        }

        if ($view == 'index' && $this->isGranted('delete', $project)) {
            $actions['trash'] = ['url' => $this->path('admin_project_delete', ['id' => $project->getId()]), 'class' => 'modal-ajax-form text-red'];
        }

        $event->setActions($actions);
    }
}
