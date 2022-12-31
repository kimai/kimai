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

final class RateResetCalculator implements CalculatorInterface
{
    public function calculate(Timesheet $record, array $changeset): void
    {
        // check if the rate was changed manually
        $changedRate = false;
        foreach (['hourlyRate', 'fixedRate', 'internalRate', 'rate'] as $field) {
            if (\array_key_exists($field, $changeset)) {
                $changedRate = true;
                break;
            }
        }

        // if no manual rate changed was applied:
        // check if a field changed, that is relevant for the rate calculation: if one was changed =>
        // reset all rates, because most users do not even see their rates and would not be able
        // to fix or empty the rate, even if they knew that the changed project has another base rate
        if (!$changedRate) {
            foreach (['project', 'activity', 'user'] as $field) {
                if (\array_key_exists($field, $changeset)) {
                    // this has room for minor improvements: entries with a manual rate might be changed
                    $record->setRate(0.00);
                    $record->setInternalRate(null);
                    $record->setHourlyRate(null);
                    $record->setFixedRate(null);
                    $record->setBillableMode(Timesheet::BILLABLE_AUTOMATIC);
                    break;
                }
            }
        }
    }

    public function getPriority(): int
    {
        // needs to run before all other
        return 50;
    }
}
