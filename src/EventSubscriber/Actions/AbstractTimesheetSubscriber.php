<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\Timesheet;
use App\Event\PageActionsEvent;

/**
 * @internal
 */
abstract class AbstractTimesheetSubscriber extends AbstractActionsSubscriber
{
    protected function timesheetActions(PageActionsEvent $event, string $routeEdit, string $routeDuplicate): void
    {
        $payload = $event->getPayload();

        /** @var Timesheet $timesheet */
        $timesheet = $payload['timesheet'];
        if ($timesheet->getId() !== null) {
            if ($timesheet->isRunning() && $this->isGranted('stop', $timesheet)) {
                $event->addAction('stop', ['url' => $this->path('stop_timesheet', ['id' => $timesheet->getId()]), 'class' => 'api-link dd-ts-stop', 'attr' => ['data-event' => 'kimai.timesheetStop kimai.timesheetUpdate', 'data-method' => 'PATCH', 'data-msg-error' => 'timesheet.stop.error', 'data-msg-success' => 'timesheet.stop.success']]);
            }

            if (!$timesheet->isRunning() && $this->isGranted('start', $timesheet)) {
                $event->addAction('repeat', ['title' => 'repeat', 'url' => $this->path('restart_timesheet', ['id' => $timesheet->getId()]), 'class' => 'api-link dd-ts-repeat', 'attr' => ['data-payload' => '{"copy": "all"}', 'data-event' => 'kimai.timesheetStart kimai.timesheetUpdate', 'data-method' => 'PATCH', 'data-msg-error' => 'timesheet.start.error', 'data-msg-success' => 'timesheet.start.success']]);
            }

            if ($this->isGranted('edit', $timesheet)) {
                $event->addEdit($this->path($routeEdit, ['id' => $timesheet->getId()]), !$event->isView('edit'), 'dd-ts-edit');
            }

            if ($this->isGranted('duplicate', $timesheet)) {
                $class = $event->isView('edit') ? '' : 'modal-ajax-form';
                $event->addAction('copy', ['title' => 'copy', 'url' => $this->path($routeDuplicate, ['id' => $timesheet->getId()]), 'class' => $class . ' dd-ts-duplicate']);
            }

            if ($event->countActions() > 0) {
                $event->addDivider();
            }

            $activity = $timesheet->getActivity();
            if ($activity !== null && $this->isGranted('edit', $activity)) {
                $event->addActionToSubmenu('details', 'activity', ['title' => 'activity', 'url' => $this->path('activity_details', ['id' => $activity->getId()])]);
            }

            $project = $timesheet->getProject();
            if ($project !== null && $this->isGranted('view', $project)) {
                $event->addActionToSubmenu('details', 'project', ['title' => 'project', 'url' => $this->path('project_details', ['id' => $project->getId()])]);

                $customer = $project->getCustomer();
                if ($customer !== null && $this->isGranted('view', $customer)) {
                    $event->addActionToSubmenu('details', 'customer', ['title' => 'customer', 'url' => $this->path('customer_details', ['id' => $customer->getId()])]);
                }
            }

            if ($event->hasSubmenu('details')) {
                $event->addDivider();
            }

            if (($event->isIndexView() || $event->isView('calendar')) && $this->isGranted('delete', $timesheet)) {
                $event->addAction('trash', [
                    'url' => $this->path('delete_timesheet', ['id' => $timesheet->getId()]),
                    'class' => 'api-link text-red dd-ts-trash',
                    'attr' => [
                        'data-event' => 'kimai.timesheetDelete',
                        'data-method' => 'DELETE',
                        'data-question' => 'confirm.delete',
                        'data-msg-error' => 'action.delete.error',
                        'data-msg-success' => 'action.delete.success'
                    ]
                ]);
            }
        }
    }
}
