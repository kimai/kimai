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
use App\Invoice\InvoiceItem;

/**
 * A calculator that sums up the invoice item records by activity.
 */
final class ActivityInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(ExportableItem $invoiceItem): string
    {
        if (null === $invoiceItem->getActivity()) {
            return '__NULL__';
        }

        return (string) $invoiceItem->getActivity()->getId();
    }

    protected function mergeSumInvoiceItem(InvoiceItem $invoiceItem, ExportableItem $entry): void
    {
        if (null === $entry->getActivity()) {
            return;
        }

        if ($entry->getActivity()->getInvoiceText() !== null) {
            $invoiceItem->setDescription($entry->getActivity()->getInvoiceText());
        } else {
            $invoiceItem->setDescription($entry->getActivity()->getName());
        }
    }

    public function getId(): string
    {
        return 'activity';
    }
}
