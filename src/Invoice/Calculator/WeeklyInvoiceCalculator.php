<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Entity\ExportableItem;
use App\Invoice\CalculatorInterface;

/**
 * A calculator that sums up the invoice item records per week.
 */
final class WeeklyInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    public function getIdentifiers(ExportableItem $invoiceItem): array
    {
        if (null === $invoiceItem->getBegin()) {
            throw new \Exception('Cannot handle invoice items without start date');
        }

        return [
            // The ISO week-numbering year ("o"), not the calendar year, because an
            // ISO week can span two calendar years. Without a year the same week
            // number from different years would be summed into one invoice entry.
            $invoiceItem->getBegin()->format('o-W')
        ];
    }

    public function getId(): string
    {
        return 'weekly';
    }
}
