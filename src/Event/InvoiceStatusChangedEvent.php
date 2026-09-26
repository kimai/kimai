<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Invoice;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Triggered after an invoice status change was saved.
 */
final class InvoiceStatusChangedEvent extends Event
{
    public function __construct(private readonly Invoice $invoice, private readonly string $statusBefore)
    {
    }

    public function getStatusBefore(): string
    {
        return $this->statusBefore;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
