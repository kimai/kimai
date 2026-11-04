<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\Activity;
use App\Event\PageActionsEvent;

final class ActivitySubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'activity';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        if (!isset($payload['activity'])) {
            return;
        }

        /** @var Activity $activity */
        $activity = $payload['activity'];

        if (!$activity instanceof Activity || $activity->getId() === null) {
            return;
        }

        if (!$event->isView('activity_details') && $this->isGranted('view', $activity)) {
            $event->addAction('details', ['title' => 'details', 'url' => $this->path('activity_details', ['id' => $activity->getId()])]);
        }

        if ($this->isGranted('edit', $activity)) {
            $event->addEdit($this->path('admin_activity_edit', ['id' => $activity->getId()]), !$event->isView('edit'));
        }

        if ($this->isGranted('permissions', $activity)) {
            $class = $event->isView('permissions') ? '' : 'modal-ajax-form';
            $event->addAction('permissions', ['title' => 'permissions', 'url' => $this->path('admin_activity_permissions', ['id' => $activity->getId()]), 'class' => $class]);
        }

        if ($event->countActions() > 0) {
            $event->addDivider();
        }

        if ($this->isGranted('view_other_timesheet')) {
            $parameters = ['activities[]' => $activity->getId()];
            if (!$activity->isGlobal()) {
                $parameters['customers[]'] = $activity->getProject()->getCustomer()->getId();
                $parameters['projects[]'] = $activity->getProject()->getId();
            }
            $event->addActionToSubmenu('filter', 'timesheet', ['title' => 'timesheet.filter', 'url' => $this->path('admin_timesheet', $parameters)]);
        }

        if ($event->hasSubmenu('filter')) {
            $event->addDivider();
        }

        if ($activity->isVisible()) {
            $route = null;
            if ($this->isGranted('create_other_timesheet')) {
                $route = 'admin_timesheet_create';
            } elseif ($this->isGranted('create_own_timesheet')) {
                $route = 'timesheet_create';
            }

            if ($route !== null) {
                $parameters = ['activity' => $activity->getId()];
                if (!$activity->isGlobal()) {
                    $parameters['project'] = $activity->getProject()->getId();
                }
                $event->addAction('create-timesheet', [
                    'title' => 'create-timesheet',
                    'icon' => 'start',
                    'url' => $this->path($route, $parameters),
                    'class' => 'modal-ajax-form'
                ]);
            }
        }

        if (($event->isIndexView() || $event->isView('project_details')) && $this->isGranted('delete', $activity)) {
            $event->addDelete($this->path('admin_activity_delete', ['id' => $activity->getId()]));
        }
    }
}
