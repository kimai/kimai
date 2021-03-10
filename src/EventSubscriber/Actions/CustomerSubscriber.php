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
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.customer' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $payload = $event->getPayload();

        if (!isset($payload['customer'])) {
            return;
        }

        /** @var Customer $customer */
        $customer = $payload['customer'];
        $view = $payload['view'];

        if ($customer->getId() === null) {
            return;
        }

        $actions = $event->getActions();

        if ($this->isGranted('view', $customer)) {
            $actions['details'] = ['url' => $this->path('customer_details', ['id' => $customer->getId()])];
        }

        if ($this->isGranted('edit', $customer)) {
            $class = ($view === 'edit' ? '' : 'modal-ajax-form');
            $actions['edit'] = ['url' => $this->path('admin_customer_edit', ['id' => $customer->getId()]), 'class' => $class];
        }

        if ($this->isGranted('permissions', $customer)) {
            $class = ($view === 'permissions' ? '' : 'modal-ajax-form');
            $actions['permissions'] = ['url' => $this->path('admin_customer_permissions', ['id' => $customer->getId()]), 'class' => $class];
        }

        if (\count($actions) > 0) {
            $actions['divider'] = null;
        }

        if ($customer->isVisible() && $this->isGranted('create_project')) {
            $actions['create'] = ['url' => $this->path('admin_project_create_with_customer', ['customer' => $customer->getId()]), 'class' => 'modal-ajax-form'];
        }

        $filters = [];

        if ($this->isGranted('view_project') || $this->isGranted('view_teamlead_project') || $this->isGranted('view_team_project')) {
            $filters['project'] = ['title' => 'project', 'translation_domain' => 'actions', 'url' => $this->path('admin_project', ['customers[]' => $customer->getId()])];
        }

        if ($this->isGranted('view_activity')) {
            $filters['activity'] = ['title' => 'activity', 'translation_domain' => 'actions', 'url' => $this->path('admin_activity', ['customers[]' => $customer->getId()])];
        }

        if ($this->isGranted('view_other_timesheet')) {
            $filters['timesheet'] = ['title' => 'timesheet', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet', ['customers[]' => $customer->getId()])];
        }

        if (\count($filters) > 0) {
            $actions['filter'] = ['children' => $filters];
        }

        if ($view == 'index' && $this->isGranted('delete', $customer)) {
            $actions['trash'] = ['url' => $this->path('admin_customer_delete', ['id' => $customer->getId()]), 'class' => 'modal-ajax-form'];
        }

        if ($this->isGranted('view_reporting') && $this->isGranted('budget_project')) {
            $actions['report_project_view'] = ['url' => $this->path('report_project_view', ['customer' => $customer->getId()]), 'icon' => 'reporting', 'translation_domain' => 'reporting'];
        }

        $event->setActions($actions);
    }
}
