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

/**
 * A calculator that sums up the timesheet records by activity.
 */
abstract class AbstractSumInvoiceCalculator extends AbstractMergedCalculator implements CalculatorInterface
{
    abstract protected function calculateSumIdentifier(Timesheet $timesheet): string;

    /**
     * @return Timesheet[]
     */
    public function getEntries()
    {
        $entries = $this->model->getEntries();
        if (empty($entries)) {
            return [];
        }

        /** @var Timesheet[] $timesheets */
        $timesheets = [];

        foreach ($entries as $entry) {
            $id = $this->calculateSumIdentifier($entry);
            if (!isset($timesheets[$id])) {
                $timesheets[$id] = new Timesheet();
            }
            $timesheet = $timesheets[$id];
            $this->mergeTimesheets($timesheet, $entry);
        }

        return array_values($timesheets);
    }
}
