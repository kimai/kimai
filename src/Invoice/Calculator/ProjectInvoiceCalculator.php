<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItem;
use App\Invoice\InvoiceItemInterface;

/**
 * A calculator that sums up the invoice item records by project.
 */
class ProjectInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(InvoiceItemInterface $invoiceItem): string
    {
        if (null === $invoiceItem->getProject()->getId()) {
            throw new \Exception('Cannot handle un-persisted projects');
        }

        return (string) $invoiceItem->getProject()->getId();
    }

    protected function mergeSumInvoiceItem(InvoiceItem $invoiceItem, InvoiceItemInterface $entry)
    {
        $invoiceItem->setProject($entry->getProject());
        $invoiceItem->setDescription($entry->getProject()->getName());
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'project';
    }
}
