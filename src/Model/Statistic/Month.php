<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

use InvalidArgumentException;

final class Month extends Timesheet
{
    private string $month;
    private int $billableDuration = 0;
    private float $billableRate = 0.00;

    /**
     * @param string|int $month
     */
    public function __construct($month)
    {
        $monthNumber = (int) $month;
        if ($monthNumber < 1 || $monthNumber > 12) {
            throw new InvalidArgumentException(
                \sprintf('Invalid month given. Expected 1-12, received "%s".', $monthNumber)
            );
        }
        $this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
    }

    public function getMonth(): string
    {
        return $this->month;
    }

    public function getMonthNumber(): int
    {
        return (int) $this->month;
    }

    public function getBillableDuration(): int
    {
        return $this->billableDuration;
    }

    public function setBillableDuration(int $billableDuration): void
    {
        $this->billableDuration = $billableDuration;
    }

    public function getBillableRate(): float
    {
        return $this->billableRate;
    }

    public function setBillableRate(float $billableRate): void
    {
        $this->billableRate = $billableRate;
    }
}
