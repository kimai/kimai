<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

final class StatisticDate extends Timesheet
{
    private \DateTimeInterface $date;
    private int $billableDuration = 0;
    private float $billableRate = 0.00;

    public function __construct(\DateTimeInterface $date)
    {
        $this->date = clone $date;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
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
