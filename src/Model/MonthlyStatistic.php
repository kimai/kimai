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
use DateTimeInterface;

final class MonthlyStatistic implements DateStatisticInterface
{
    /**
     * @var array<string, array<int<1, 12>, StatisticDate>>
     */
    private array $years = [];
    private DateTimeInterface $begin;
    private DateTimeInterface $end;
    private User $user;

    public function __construct(DateTimeInterface $begin, DateTimeInterface $end, User $user)
    {
        $this->begin = clone $begin;
        $this->end = clone $end;
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
        $begin = DateTime::createFromInterface($this->begin);
        $begin->setTime(0, 0, 0);

        $tmp = clone $begin;
        $day = (int) $begin->format('d');

        while ($tmp < $this->end) {
            $curYear = (string) $tmp->format('Y');
            if (!isset($years[$curYear])) {
                $year = [];
                for ($i = 1; $i < 13; $i++) {
                    $date = clone $begin;
                    // financial years do NOT start at the first of the month, do not reset day to 1
                    $date->setDate((int) $curYear, $i, $day);
                    $date->setTime(0, 0, 0);
                    if ($date < $begin || $date > $this->end) {
                        continue;
                    }
                    $year[$i] = new StatisticDate($date);
                }
                $years[$curYear] = $year;
            }
            $tmp->modify('+1 month');
        }
        $this->years = $years; // @phpstan-ignore assign.propertyType
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

    /**
     * @return StatisticDate[]
     */
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

    /**
     * For unified frontend access
     *
     * @return StatisticDate[]
     */
    public function getData(): array
    {
        return $this->getMonths();
    }

    public function getMonthByDateTime(DateTimeInterface $date): ?StatisticDate
    {
        return $this->getMonth($date->format('Y'), $date->format('m'));
    }

    public function getByDateTime(DateTimeInterface $date): ?StatisticDate
    {
        return $this->getMonthByDateTime($date);
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
     * @return DateTimeInterface[]
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
