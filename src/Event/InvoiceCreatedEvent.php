<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Invoice;
use App\Invoice\InvoiceModel;
use App\Webhook\Attribute\AsWebhook;

#[AsWebhook(name: 'invoice.created', description: 'Triggered after an invoice was created', payload: 'object.getInvoice()')]
final class InvoiceCreatedEvent extends AbstractInvoiceEvent
{
    public function __construct(Invoice $invoice, private readonly InvoiceModel $model)
    {
        parent::__construct($invoice);
    }

    public function getInvoiceModel(): InvoiceModel
    {
        return $this->model;
    }
}
