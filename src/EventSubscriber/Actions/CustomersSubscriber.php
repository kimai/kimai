<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Repository\Query\CustomerQuery;

class CustomersSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'customers';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var CustomerQuery $query */
        $query = $payload['query'];

        $event->addSearchToggle($query);
        $event->addColumnToggle('#modal_customer_admin');
        $event->addQuickExport($this->path('customer_export'));

        if ($this->isGranted('create_customer')) {
            $event->addCreate($this->path('admin_customer_create'));
        }

        if ($this->isGranted('system_configuration')) {
            $event->addAction('settings', ['url' => $this->path('system_configuration_section', ['section' => 'customer']), 'class' => 'modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('customer.html'));
    }
}
