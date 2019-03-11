<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

/**
 * A static helper class for re-usable functionality.
 */
class Util
{
    /**
     * Calculates the rate for a hourly rate and a given duration in seconds.
     *
     * @param float $hourlyRate
     * @param int $seconds
     * @return float
     */
    public static function calculateRate(float $hourlyRate, int $seconds): float
    {
        $rate = (float) ($hourlyRate * ($seconds / 3600));
        $rate = round($rate, 2);

        return $rate;
    }
}
