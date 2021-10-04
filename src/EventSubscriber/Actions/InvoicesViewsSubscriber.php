<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class InvoicesViewsSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'invoices_views';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addAction('invoice', ['url' => $this->path('invoice'), 'title' => 'invoice.create']);
        $event->addAction('list', ['url' => $this->path('admin_invoice_list'), 'title' => 'invoice.title']);

        if ($this->isGranted('manage_invoice_template')) {
            $event->addAction('invoice-template', ['url' => $this->path('admin_invoice_template'), 'title' => 'admin_invoice_template.title']);
        }
    }
}
