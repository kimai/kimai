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

final class InvoiceCreatedMultipleEvent extends Event
{
    /**
     * @param Invoice[] $invoices
     */
    private $invoices;

    /**
     * @param Invoice[] $invoices
     */
    public function __construct(array $invoices)
    {
        $this->invoices = $invoices;
    }

    /**
     * @return Invoice[]
     */
    public function getInvoices(): array
    {
        return $this->invoices;
    }
}
