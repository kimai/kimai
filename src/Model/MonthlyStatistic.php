<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\User;
use App\Model\Statistic\StatisticDate;
use DateTime;

final class MonthlyStatistic
{
    /**
     * @var array<string, array<int, StatisticDate>>
     */
    private $years = [];
    private $begin;
    private $end;
    private $user;

    public function __construct(DateTime $begin, DateTime $end, User $user)
    {
        $this->begin = $begin;
        $this->end = $end;
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    private function setupYears(): void
    {
        if (!empty($this->years)) {
            return;
        }

        $years = [];
        $tmp = clone $this->begin;
        while ($tmp < $this->end) {
            $curYear = $tmp->format('Y');
            if (!isset($years[$curYear])) {
                $year = [];
                for ($i = 1; $i < 13; $i++) {
                    $date = clone $this->begin;
                    // financial years do NOT start at the first of the month, do not reset day to 1
                    $date->setDate((int) $curYear, $i, (int) $this->begin->format('d'));
                    $date->setTime(0, 0, 0);
                    if ($date < $this->begin || $date > $this->end) {
                        continue;
                    }
                    $year[$i] = new StatisticDate($date);
                }
                $years[$curYear] = $year;
            }
            $tmp->modify('+1 month');
        }
        $this->years = $years;
    }

    /**
     * @return string[]
     */
    public function getYears(): array
    {
        $this->setupYears();

        return array_keys($this->years);
    }

    public function getYear(string $year): ?array
    {
        $this->setupYears();
        if (!isset($this->years[$year])) {
            return null;
        }

        return $this->years[$year];
    }

    public function getMonths(): array
    {
        $this->setupYears();
        $all = [];

        foreach ($this->years as $number => $months) {
            foreach ($months as $monthNumber => $statisticDate) {
                $all[] = $statisticDate;
            }
        }

        return $all;
    }

    public function getMonth(string $year, string $month): ?StatisticDate
    {
        $this->setupYears();

        $month = (int) $month;

        if (!isset($this->years[$year]) || !isset($this->years[$year][$month])) {
            return null;
        }

        return $this->years[$year][$month];
    }

    /**
     * @return DateTime[]
     */
    public function getDateTimes(): array
    {
        $this->setupYears();
        $all = [];

        foreach ($this->years as $number => $months) {
            foreach ($months as $monthNumber => $statisticDate) {
                $all[] = $statisticDate->getDate();
            }
        }

        return $all;
    }
}
