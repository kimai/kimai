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
        /** @var array<string, InvoiceDocument|null|bool|string> $payload */
        $payload = $event->getPayload();
        if (!\is_array($payload)) {
            return;
        }

        /** @var InvoiceDocument|null $document */
        $document = \array_key_exists('document', $payload) ? $payload['document'] : null;

        if ($document === null) {
            return;
        }

        if (!$this->isGranted('manage_invoice_template')) {
            return;
        }

        /** @var bool $inUse */
        $inUse = \array_key_exists('in_use', $payload) ? $payload['in_use'] : false;
        /** @var string $token */
        $token = \array_key_exists('token', $payload) ? $payload['token'] : null;

        $event->addAction('download', ['url' => $this->path('admin_invoice_document_download', ['document' => $document->getId()])]);

        if ($document->isTwig()) {
            $event->addAction('reload', ['url' => $this->path('admin_invoice_document_reload', ['document' => $document->getId()])]);
        }

        if (!$inUse) {
            $event->addDelete($this->path('invoice_document_delete', ['id' => $document->getId(), 'token' => $token]), false);
        }
    }
}
