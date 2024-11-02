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

final class ProjectSubscriber extends AbstractActionsSubscriber
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
        $customer = $project->getCustomer();

        if ($project->getId() === null || $customer === null) {
            return;
        }

        $isListingView = $event->isIndexView() || $event->isCustomView();

        if (!$event->isView('project_details') && $this->isGranted('view', $project)) {
            $event->addAction('details', ['title' => 'details', 'url' => $this->path('project_details', ['id' => $project->getId()])]);
        }

        if ($this->isGranted('edit', $project)) {
            $event->addEdit($this->path('admin_project_edit', ['id' => $project->getId()]), !$event->isView('edit'));
        }

        if ($this->isGranted('permissions', $project)) {
            $class = $event->isView('permissions') ? '' : 'modal-ajax-form';
            $event->addAction('permissions', ['title' => 'permissions', 'url' => $this->path('admin_project_permissions', ['id' => $project->getId()]), 'class' => $class]);
        }

        if ($event->countActions() > 0) {
            $event->addDivider();
        }

        if ($this->isGranted('view_activity')) {
            $event->addActionToSubmenu('filter', 'activity', ['title' => 'activity.filter', 'url' => $this->path('admin_activity', ['customers[]' => $customer->getId(), 'projects[]' => $project->getId()])]);
        }

        if ($this->isGranted('view_other_timesheet')) {
            $event->addActionToSubmenu('filter', 'timesheet', ['title' => 'timesheet.filter', 'url' => $this->path('admin_timesheet', ['customers[]' => $customer->getId(), 'projects[]' => $project->getId()])]);
        }

        if ($this->isGranted('create_export')) {
            $event->addActionToSubmenu('filter', 'export', ['title' => 'export', 'url' => $this->path('export', ['customers[]' => $customer->getId(), 'projects[]' => $project->getId(), 'exported' => 1, 'daterange' => ''])]);
        }

        if ($event->hasSubmenu('filter')) {
            $event->addDivider();
        }

        if ($isListingView) {
            if ($project->isVisible() && $customer->isVisible() && $this->isGranted('create_activity')) {
                $event->addAction('create-activity', [
                    'icon' => 'create',
                    'url' => $this->path('admin_activity_create_with_project', ['project' => $project->getId()]),
                    'class' => 'modal-ajax-form'
                ]);
            }
        }

        if (\array_key_exists('token', $payload) && $this->isGranted('edit', $project) && $this->isGranted('create_project')) {
            $event->addAction(
                'copy',
                ['title' => 'copy', 'url' => $this->path('admin_project_duplicate', ['id' => $project->getId(), 'token' => $payload['token']])]
            );
        }

        if (($event->isIndexView() || $event->isView('customer_details')) && $this->isGranted('delete', $project)) {
            $event->addDelete($this->path('admin_project_delete', ['id' => $project->getId()]));
        }

        if (!$event->isView('project_details_report') && $this->isGranted('report:project') && $this->isGranted('details', $project)) {
            $event->addAction('report_project_details', ['title' => 'report_project_details', 'translation_domain' => 'reporting', 'url' => $this->path('report_project_details', ['project' => $project->getId()]), 'icon' => 'reporting']);
        }
    }
}
