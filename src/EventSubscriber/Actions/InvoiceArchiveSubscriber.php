<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class InvoiceArchiveSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.invoice_details' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $actions = $event->getActions();

        if ($this->isGranted('view_invoice')) {
            $actions['back'] = ['url' => $this->path('invoice'), 'translation_domain' => 'actions'];
        }

        $actions['visibility'] = '#modal_invoices';
        $actions['download'] = ['url' => $this->path('invoice_export'), 'class' => 'toolbar-action'];
        $actions['help'] = ['url' => $this->documentationLink('invoices.html'), 'target' => '_blank'];

        $event->setActions($actions);
    }
}
