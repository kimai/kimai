<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\Invoice;
use App\Event\PageActionsEvent;

final class InvoiceSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'invoice';
    }

    /**
     * Status changes are POST requests, whose CSRF token is submitted in the request body.
     *
     * @return array<string, mixed>
     */
    private function createStatusAction(Invoice $invoice, string $status): array
    {
        return [
            'url' => '#',
            'onclick' => 'return kimaiInvoiceStatus(this)',
            'attr' => [
                'data-href' => $this->path('admin_invoice_status', ['id' => $invoice->getId(), 'status' => $status]),
            ],
        ];
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var Invoice $invoice */
        $invoice = $payload['invoice'];

        if ($invoice->getId() === null) {
            return;
        }

        $allowCreate = $this->isGranted('create_invoice');
        $allowView = $this->isGranted('view_invoice');

        if ($allowCreate) {
            $event->addEdit($this->path('admin_invoice_edit', ['id' => $invoice->getId()]));
        }

        if ($allowView) {
            $event->addAction('download', ['url' => $this->path('admin_invoice_download', ['id' => $invoice->getId()]), 'target' => '_blank']);
        }

        if ($event->countActions() > 0) {
            $event->addDivider();
        }

        if ($allowCreate) {
            if (!$invoice->isPending()) {
                $event->addAction('invoice.pending', $this->createStatusAction($invoice, 'pending'));
            } else {
                $event->addAction('invoice.paid', ['url' => $this->path('admin_invoice_paid', ['id' => $invoice->getId()]), 'class' => 'modal-ajax-form']);
            }
        }

        $allowDelete = $this->isGranted('delete_invoice');
        if (!$invoice->isCanceled()) {
            $event->addAction('invoice.cancel', $this->createStatusAction($invoice, 'canceled'));
        }

        if ($allowDelete) {
            $event->addDivider();
            $event->addDelete($this->path('delete_invoice', ['id' => $invoice->getId()]), false, 'kimai.invoiceUpdate');
        }
    }
}
