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
 * This event can be used, to dynamically add meta-fields to invoices.
 *
 * Do not use directly, call InvoiceService::loadMetaFields() instead.
 */
final class InvoiceMetaDefinitionEvent extends Event
{
    public function __construct(private readonly Invoice $invoice)
    {
    }

    public function getEntity(): Invoice
    {
        return $this->invoice;
    }
}
