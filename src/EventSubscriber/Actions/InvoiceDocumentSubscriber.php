<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Model\InvoiceDocument;

final class InvoiceDocumentSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'invoice_document';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var InvoiceDocument|null $document */
        $document = $payload['document'];

        if ($document === null) {
            return;
        }

        if ($this->isGranted('manage_invoice_template')) {
            $event->addDelete($this->path('invoice_document_delete', ['id' => $document->getId(), 'token' => $payload['token']]), false);
        }
    }
}
