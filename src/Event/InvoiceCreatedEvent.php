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
use Symfony\Contracts\EventDispatcher\Event;

final class InvoiceCreatedEvent extends Event
{
    private $invoice;
    private $model;

    public function __construct(Invoice $invoice, InvoiceModel $model)
    {
        $this->invoice = $invoice;
        $this->model = $model;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getInvoiceModel(): InvoiceModel
    {
        return $this->model;
    }
}
