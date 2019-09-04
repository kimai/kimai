<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItemInterface;

/**
 * A calculator that sums up the invoice item records for each day.
 */
class DateInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(InvoiceItemInterface $invoiceItem): string
    {
        if (null === $invoiceItem->getBegin()) {
            throw new \Exception('Cannot handle invoice items without start date');
        }

        return $invoiceItem->getBegin()->format('Y-m-d');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'date';
    }
}
