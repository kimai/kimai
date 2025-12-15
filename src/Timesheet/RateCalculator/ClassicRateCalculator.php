<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\RateCalculator;

final class ClassicRateCalculator implements RateCalculatorMode
{
    /**
     * Calculates the rate by an hourly rate and a given duration in seconds.
     */
    public function calculateRate(float $hourlyRate, int $seconds): float
    {
        $rate = $hourlyRate * ($seconds / 3600);

        return round($rate, 4);
    }

    /**
     * Does not round the duration, we keep the original sum.
     */
    public function roundDuration(int $seconds): int
    {
        return $seconds;
    }
}
