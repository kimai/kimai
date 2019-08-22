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
 * An abstract calculator that sums up the timesheet records.
 */
abstract class AbstractSumInvoiceCalculator extends AbstractMergedCalculator implements CalculatorInterface
{
    abstract protected function calculateSumIdentifier(Timesheet $timesheet): string;

    /**
     * @return InvoiceItem[]
     */
    public function getEntries()
    {
        $entries = $this->model->getEntries();
        if (empty($entries)) {
            return [];
        }

        /** @var InvoiceItem[] $invoiceItems */
        $invoiceItems = [];

        foreach ($entries as $entry) {
            $id = $this->calculateSumIdentifier($entry);

            if (null !== $entry->getFixedRate()) {
                $id = $id . '_fixed_' . (string) $entry->getFixedRate();
            } else {
                $id = $id . '_hourly_' . (string) $entry->getHourlyRate();
            }

            if (!isset($invoiceItems[$id])) {
                $invoiceItems[$id] = new InvoiceItem();
            }
            $timesheet = $invoiceItems[$id];
            $this->mergeTimesheets($timesheet, $entry);
        }

        return array_values($invoiceItems);
    }
}
