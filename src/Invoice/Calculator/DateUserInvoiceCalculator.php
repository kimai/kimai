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
 * A calculator that sums up the invoice item records for each day and user.
 */
final class DateUserInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    public function getIdentifiers(ExportableItem $invoiceItem): array
    {
        if (null === $invoiceItem->getBegin()) {
            throw new \Exception('Cannot handle invoice items without start date');
        }

        if ($invoiceItem->getUser()?->getId() === null) {
            throw new \Exception('Cannot handle un-persisted users');
        }

        return [
            $invoiceItem->getBegin()->format('Y-m-d'),
            $invoiceItem->getUser()->getId()
        ];
    }

    public function getId(): string
    {
        return 'date_user';
    }
}
