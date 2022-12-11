<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Calculator;

use App\Entity\Timesheet;
use App\Timesheet\CalculatorInterface;

/**
 * Implementation to calculate the billable field for a timesheet record.
 */
final class BillableCalculator implements CalculatorInterface
{
    public function calculate(Timesheet $record, array $changeset): void
    {
        switch ($record->getBillableMode()) {
            case Timesheet::BILLABLE_NO:
                $record->setBillable(false);
                break;
            case Timesheet::BILLABLE_YES:
                $record->setBillable(true);
                break;
            case Timesheet::BILLABLE_AUTOMATIC:
                $billable = true;

                $activity = $record->getActivity();
                if ($activity !== null && !$activity->isBillable()) {
                    $billable = false;
                }

                $project = $record->getProject();
                if ($billable && $project !== null && !$project->isBillable()) {
                    $billable = false;
                }

                if ($billable && $project !== null) {
                    $customer = $project->getCustomer();
                    if ($customer !== null && !$customer->isBillable()) {
                        $billable = false;
                    }
                }

                $record->setBillable($billable);
                break;
        }
    }

    public function getPriority(): int
    {
        return 100;
    }
}
