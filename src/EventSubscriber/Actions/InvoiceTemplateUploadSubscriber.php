<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class InvoiceTemplateUploadSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'invoice_upload';
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($event->isIndexView() && $this->isGranted('manage_invoice_template')) {
            $event->addBack($this->path('admin_invoice_template'));
        }

        $event->addHelp($this->documentationLink('invoices.html'));
    }
}
