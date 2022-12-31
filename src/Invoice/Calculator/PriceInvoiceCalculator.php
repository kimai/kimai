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
 * A calculator that sums up the invoice item records by price.
 */
final class PriceInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(ExportableItem $invoiceItem): string
    {
        if (null !== $invoiceItem->getFixedRate()) {
            return 'fixed_' . $invoiceItem->getFixedRate();
        }

        return 'hourly_' . $invoiceItem->getHourlyRate();
    }

    public function getId(): string
    {
        return 'price';
    }
}
