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
final class Util
{
    private function __construct()
    {
    }

    /**
     * Calculates the rate by an hourly rate and a given duration in seconds.
     */
    public static function calculateRate(float $hourlyRate, int $seconds): float
    {
        $rate = $hourlyRate * ($seconds / 3600);

        return round($rate, 4);
    }
}
