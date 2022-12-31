<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

use DateTime;

final class Day extends Timesheet
{
    private int $totalDurationBillable = 0;
    private array $details = [];

    public function __construct(private DateTime $day, int $duration, float $rate)
    {
        $this->setTotalDuration($duration);
        $this->setTotalRate($rate);
    }

    public function getDay(): DateTime
    {
        return $this->day;
    }

    public function getTotalDurationBillable(): int
    {
        return $this->totalDurationBillable;
    }

    public function setTotalDurationBillable(int $seconds): void
    {
        $this->totalDurationBillable = $seconds;
    }

    public function setDetails(array $details): Day
    {
        $this->details = $details;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
