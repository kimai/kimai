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
    public static function getActionName(): string
    {
        return 'project';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var Project $project */
        $project = $payload['project'];

        if ($project->getId() === null) {
            return;
        }

        if (!$event->isView('project_details') && $this->isGranted('view', $project)) {
            $event->addAction('details', ['url' => $this->path('project_details', ['id' => $project->getId()])]);
        }

        if ($this->isGranted('edit', $project)) {
            $class = $event->isView('edit') ? '' : 'modal-ajax-form';
            $event->addAction('edit', ['url' => $this->path('admin_project_edit', ['id' => $project->getId()]), 'class' => $class]);
        }

        if ($this->isGranted('permissions', $project)) {
            $class = $event->isView('permissions') ? '' : 'modal-ajax-form';
            $event->addAction('permissions', ['url' => $this->path('admin_project_permissions', ['id' => $project->getId()]), 'class' => $class]);
        }

        if ($event->countActions() > 0) {
            $event->addDivider();
        }

        if ($this->isGranted('view_activity')) {
            $event->addActionToSubmenu('filter', 'activity', ['title' => 'activity', 'translation_domain' => 'actions', 'url' => $this->path('admin_activity', ['customers[]' => $project->getCustomer()->getId(), 'projects[]' => $project->getId()])]);
        }

        if ($this->isGranted('view_other_timesheet')) {
            $event->addActionToSubmenu('filter', 'timesheet', ['title' => 'timesheet', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet', ['customers[]' => $project->getCustomer()->getId(), 'projects[]' => $project->getId()])]);
        }

        if ($event->hasSubmenu('filter')) {
            $event->addDivider();
        }

        if (!$event->isView('project_details')) {
            if ($project->isVisible() && $project->getCustomer()->isVisible() && $this->isGranted('create_activity')) {
                $event->addAction('create-activity', [
                    'icon' => 'create',
                    'url' => $this->path('admin_activity_create_with_project', ['project' => $project->getId()]),
                    'class' => 'modal-ajax-form'
                ]);
            }
        }

        if ($this->isGranted('edit', $project) && $this->isGranted('create_project')) {
            $event->addAction(
                'copy',
                ['url' => $this->path('admin_project_duplicate', ['id' => $project->getId()])]
            );
        }

        if (($event->isIndexView() || $event->isView('customer_details')) && $this->isGranted('delete', $project)) {
            $event->addDelete($this->path('admin_project_delete', ['id' => $project->getId()]));
        }

        if ($project->isVisible() && $this->isGranted('view_reporting') && $this->isGranted('details', $project)) {
            $event->addAction('report_project_details', ['url' => $this->path('report_project_details', ['project' => $project->getId()]), 'icon' => 'reporting', 'translation_domain' => 'reporting']);
        }
    }
}
