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

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('view_invoice')) {
            $event->addBack($this->path('invoice'));
        }
        $event->addSearchToggle();
        $event->addColumnToggle('#modal_invoices');
        $event->addQuickExport($this->path('invoice_export'));
        $event->addHelp($this->documentationLink('invoices.html'));
    }
}
