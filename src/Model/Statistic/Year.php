<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

final class Year extends Timesheet
{
    /**
     * @var Month[]
     */
    private $months = [];

    public function __construct(private string $year)
    {
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
}
