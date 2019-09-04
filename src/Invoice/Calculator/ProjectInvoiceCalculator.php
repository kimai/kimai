<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Entity\Timesheet;
use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItem;

/**
 * A calculator that sums up the timesheet records by project.
 */
class ProjectInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(Timesheet $timesheet): string
    {
        if (null === $timesheet->getProject()->getId()) {
            throw new \Exception('Cannot handle un-persisted projects');
        }

        return (string) $timesheet->getProject()->getId();
    }

    protected function mergeSumTimesheet(InvoiceItem $invoiceItem, Timesheet $entry)
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
