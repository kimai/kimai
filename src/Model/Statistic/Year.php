<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

final class Year
{
    private $year;
    /**
     * @var Month[]
     */
    private $months = [];
    private $totalDuration = 0;
    private $totalRate = 0.00;
    private $totalInternalRate = 0.00;

    public function __construct(string $year)
    {
        $this->year = $year;
    }

    public function getYear(): string
    {
        return $this->year;
    }

    public function setMonth(Month $month): Year
    {
        $this->months[$month->getMonthNumber()] = $month;

        return $this;
    }

    public function getMonth(int $month): ?Month
    {
        if (isset($this->months[$month])) {
            return $this->months[$month];
        }

        return null;
    }

    /**
     * @return Month[]
     */
    public function getMonths(): array
    {
        return array_values($this->months);
    }

    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    public function setTotalDuration(int $totalDuration): void
    {
        $this->totalDuration = $totalDuration;
    }

    public function getTotalRate(): float
    {
        return $this->totalRate;
    }

    public function setTotalRate(float $totalRate): void
    {
        $this->totalRate = $totalRate;
    }

    public function getTotalInternalRate(): float
    {
        return $this->totalInternalRate;
    }

    public function setTotalInternalRate(float $totalInternalRate): void
    {
        $this->totalInternalRate = $totalInternalRate;
    }
}
