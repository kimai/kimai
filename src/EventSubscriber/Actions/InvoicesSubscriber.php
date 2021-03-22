<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class InvoicesSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'invoices';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addColumnToggle('#modal_invoice');

        $event->addAction('list', ['url' => $this->path('admin_invoice_list')]);

        if ($this->isGranted('manage_invoice_template')) {
            $event->addAction('invoice-template', ['url' => $this->path('admin_invoice_template')]);
        }

        if ($this->isGranted('system_configuration')) {
            $event->addAction('settings', ['url' => $this->path('system_configuration_section', ['section' => 'invoice']), 'class' => 'modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('invoices.html'));
    }
}
