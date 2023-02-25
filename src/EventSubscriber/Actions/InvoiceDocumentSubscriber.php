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
        /** @var array<string, InvoiceDocument|null|bool> $payload */
        $payload = $event->getPayload();
        if (!\is_array($payload)) {
            return;
        }

        /** @var InvoiceDocument|null $document */
        $document = $payload['document'];
        $inUse = \array_key_exists('in_use', $payload) ? $payload['in_use'] : false;

        if ($document === null) {
            return;
        }

        if (!$this->isGranted('manage_invoice_template')) {
            return;
        }

        if (!$inUse) {
            $event->addDelete($this->path('invoice_document_delete', ['id' => $document->getId(), 'token' => $payload['token']]), false);
        }

        if ($document->isTwig()) {
            $event->addAction('Reload', ['url' => $this->path('admin_invoice_document_reload', ['document' => $document->getId()])]);
        }
    }
}
