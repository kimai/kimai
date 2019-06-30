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
 * A calculator that sums up all timesheet records from the model and returns only one
 * entry for a compact invoice version.
 */
class ShortInvoiceCalculator extends AbstractMergedCalculator implements CalculatorInterface
{
    /**
     * @return Timesheet[]
     */
    public function getEntries()
    {
        $entries = $this->model->getEntries();
        if (empty($entries)) {
            return [];
        }

        $timesheet = new Timesheet();

        foreach ($entries as $entry) {
            $this->mergeTimesheets($timesheet, $entry);
        }

        $timesheet->setFixedRate($timesheet->getRate());
        $timesheet->setHourlyRate($timesheet->getRate());

        return [$timesheet];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'short';
    }
}
