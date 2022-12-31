<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\InvoiceTemplate;
use App\Event\PageActionsEvent;

final class InvoiceTemplateSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'invoice_template';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var InvoiceTemplate $template */
        $template = $payload['template'];

        if ($template->getId() === null) {
            return;
        }

        if ($this->isGranted('manage_invoice_template')) {
            $event->addAction('edit', ['url' => $this->path('admin_invoice_template_edit', ['id' => $template->getId()]), 'class' => 'modal-ajax-form']);
            $event->addAction('copy', ['url' => $this->path('admin_invoice_template_copy', ['id' => $template->getId()]), 'class' => 'modal-ajax-form']);
            $event->addDelete($this->path('admin_invoice_template_delete', ['id' => $template->getId(), 'csrfToken' => $payload['token']]), false);
        }
    }
}
