<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\RateCalculator;

interface RateCalculatorMode
{
    /**
     * Calculates the rate by an hourly rate and a given duration in seconds.
     */
    public function calculateRate(float $hourlyRate, int $seconds): float;

    /**
     * * Makes sure tha the duration is fully rounded.
     */
    public function roundDuration(int $seconds): int;
}
