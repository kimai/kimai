<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\Customer;
use App\Event\PageActionsEvent;

class CustomerSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'customer';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var Customer $customer */
        $customer = $payload['customer'];

        if ($customer->getId() === null) {
            return;
        }

        if (!$event->isView('customer_details') && $this->isGranted('view', $customer)) {
            $event->addAction('details', ['url' => $this->path('customer_details', ['id' => $customer->getId()])]);
        }

        if ($this->isGranted('edit', $customer)) {
            $class = $event->isView('edit') ? '' : 'modal-ajax-form';
            $event->addAction('edit', ['url' => $this->path('admin_customer_edit', ['id' => $customer->getId()]), 'class' => $class]);
        }

        if ($this->isGranted('permissions', $customer)) {
            $class = $event->isView('permissions') ? '' : 'modal-ajax-form';
            $event->addAction('permissions', ['url' => $this->path('admin_customer_permissions', ['id' => $customer->getId()]), 'class' => $class]);
        }

        if ($event->countActions() > 0) {
            $event->addDivider();
        }

        if ($this->isGranted('view_project') || $this->isGranted('view_teamlead_project') || $this->isGranted('view_team_project')) {
            $event->addActionToSubmenu('filter', 'project', ['title' => 'project.filter', 'translation_domain' => 'actions', 'url' => $this->path('admin_project', ['customers[]' => $customer->getId()])]);
        }

        if ($this->isGranted('view_activity')) {
            $event->addActionToSubmenu('filter', 'activity', ['title' => 'activity.filter', 'translation_domain' => 'actions', 'url' => $this->path('admin_activity', ['customers[]' => $customer->getId()])]);
        }

        if ($this->isGranted('view_other_timesheet')) {
            $event->addActionToSubmenu('filter', 'timesheet', ['title' => 'timesheet.filter', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet', ['customers[]' => $customer->getId()])]);
        }

        if ($event->hasSubmenu('filter')) {
            $event->addDivider();
        }

        if (!$event->isView('customer_details')) {
            if ($customer->isVisible() && $this->isGranted('create_project')) {
                $event->addAction('create-project', [
                    'icon' => 'create',
                    'url' => $this->path('admin_project_create_with_customer', ['customer' => $customer->getId()]),
                    'class' => 'modal-ajax-form'
                ]);
            }
        }

        if ($event->isIndexView() && $this->isGranted('delete', $customer)) {
            $event->addDelete($this->path('admin_customer_delete', ['id' => $customer->getId()]));
        }

        if ($this->isGranted('view_reporting') && $this->isGranted('budget_any', 'project')) {
            $event->addAction('report_project_view', ['url' => $this->path('report_project_view', ['customer' => $customer->getId()]), 'icon' => 'reporting', 'translation_domain' => 'reporting']);
        }
    }
}
