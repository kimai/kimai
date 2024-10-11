<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Calculator;

final class WorkingTimeCalculatorNone implements WorkingTimeCalculator
{
    public function getWorkHoursForDay(\DateTimeInterface $dateTime): int
    {
        return 0;
    }

    public function isWorkDay(\DateTimeInterface $dateTime): bool
    {
        // we don't know it, so we must assume every day is a a working day
        return true;
    }
}
