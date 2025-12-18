<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\RateCalculator;

final class DecimalRateCalculator implements RateCalculatorMode
{
    /**
     * Calculates the rate by an hourly rate and a given duration in seconds.
     */
    public function calculateRate(float $hourlyRate, int $seconds): float
    {
        $rate = $hourlyRate * round(($seconds / 3600), 2, PHP_ROUND_HALF_UP);

        return round($rate, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Makes sure tha the duration is full compatible with decimal format, stripping away overflowing seconds.
     */
    public function roundDuration(int $seconds): int
    {
        $decimal = round(($seconds / 3600), 2, PHP_ROUND_HALF_UP);

        return (int) round(($decimal * 3600), 0, PHP_ROUND_HALF_UP);
    }
}
