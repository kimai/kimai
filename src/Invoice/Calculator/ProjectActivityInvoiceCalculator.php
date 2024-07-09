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
 * A calculator that sums up the invoice item records by project and activity.
 */
final class ProjectActivityInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    public function getIdentifiers(ExportableItem $invoiceItem): array
    {
        if ($invoiceItem->getProject() === null) {
            throw new \Exception('Cannot handle invoice items without project');
        }

        if ($invoiceItem->getProject()->getId() === null) {
            throw new \Exception('Cannot handle un-persisted projects');
        }

        if ($invoiceItem->getActivity() === null) {
            throw new \Exception('Cannot handle invoice items without activity');
        }

        if ($invoiceItem->getActivity()->getId() === null) {
            throw new \Exception('Cannot handle un-persisted activities');
        }

        return [
            $invoiceItem->getProject()->getId(),
            $invoiceItem->getActivity()->getId()
        ];
    }

    protected function mergeSumInvoiceItem(InvoiceItem $invoiceItem, ExportableItem $entry): void
    {
        if ($entry->getProject() === null) {
            return;
        }

        if ($entry->getProject()->getInvoiceText() !== null) {
            $invoiceItem->setDescription($entry->getProject()->getInvoiceText());
        } else {
            $invoiceItem->setDescription($entry->getProject()->getName());
        }
    }

    public function getId(): string
    {
        return 'project_activity';
    }
}
