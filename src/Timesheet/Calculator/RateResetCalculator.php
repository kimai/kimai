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
        foreach (['hourlyRate', 'fixedRate', 'internalRate', 'rate'] as $field) {
            if (\array_key_exists($field, $changeset)) {
                return;
            }
        }

        // if no manual rate changed was applied:
        // check if a field changed, that is relevant for the rate calculation
        // reset all rates, because most users do not even see their rates and would not be able
        // to change the rate, even if they knew that the changed project has another base rate
        foreach (['project', 'activity', 'user'] as $field) {
            if (\array_key_exists($field, $changeset)) {
                $record->resetRates();
                break;
            }
        }
    }

    public function getPriority(): int
    {
        // needs to run before all other
        return 50;
    }
}
