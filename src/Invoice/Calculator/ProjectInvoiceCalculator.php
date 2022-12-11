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
 * A calculator that sums up the invoice item records by project.
 */
final class ProjectInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(ExportableItem $invoiceItem): string
    {
        if (null === $invoiceItem->getProject()->getId()) {
            throw new \Exception('Cannot handle un-persisted projects');
        }

        return (string) $invoiceItem->getProject()->getId();
    }

    protected function mergeSumInvoiceItem(InvoiceItem $invoiceItem, ExportableItem $entry): void
    {
        if ($entry->getProject()->getInvoiceText() !== null) {
            $invoiceItem->setDescription($entry->getProject()->getInvoiceText());
        } else {
            $invoiceItem->setDescription($entry->getProject()->getName());
        }
    }

    public function getId(): string
    {
        return 'project';
    }
}
