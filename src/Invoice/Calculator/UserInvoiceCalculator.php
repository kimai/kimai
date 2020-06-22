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
 * A calculator that sums up the invoice item records by user.
 */
class UserInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(InvoiceItemInterface $invoiceItem): string
    {
        if (null === $invoiceItem->getUser()->getId()) {
            throw new \Exception('Cannot handle un-persisted user');
        }

        return (string) $invoiceItem->getUser()->getId();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'user';
    }
}
