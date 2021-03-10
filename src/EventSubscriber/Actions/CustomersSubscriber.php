<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class CustomersSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.customers' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $actions = $event->getActions();

        $actions['search'] = ['class' => 'search-toggle visible-xs-inline'];
        $actions['visibility'] = ['modal' => '#modal_customer_admin'];
        $actions['download'] = ['url' => $this->path('customer_export'), 'class' => 'toolbar-action'];

        if ($this->isGranted('create_customer')) {
            $actions['create'] = ['url' => $this->path('admin_customer_create'), 'class' => 'modal-ajax-form'];
        }

        $actions['help'] = ['url' => $this->documentationLink('customer.html'), 'target' => '_blank'];

        $event->setActions($actions);
    }
}
