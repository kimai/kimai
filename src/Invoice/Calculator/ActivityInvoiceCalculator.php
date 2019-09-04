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
 * A calculator that sums up the timesheet records by activity.
 */
class ActivityInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(Timesheet $timesheet): string
    {
        if (null === $timesheet->getActivity()->getId()) {
            throw new \Exception('Cannot handle un-persisted activities');
        }

        return (string) $timesheet->getActivity()->getId();
    }

    protected function mergeSumTimesheet(InvoiceItem $invoiceItem, Timesheet $entry)
    {
        $invoiceItem->setActivity($entry->getActivity());
        $invoiceItem->setDescription($entry->getActivity()->getName());
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'activity';
    }
}
