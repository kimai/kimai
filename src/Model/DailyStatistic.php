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

final class DailyStatistic
{
    /**
     * @var array<string, StatisticDate>
     */
    private $days = [];
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

    private function setupDays(): void
    {
        if (!empty($this->days)) {
            return;
        }

        $tmp = clone $this->begin;
        while ($tmp < $this->end) {
            $id = $tmp->format('Y-m-d');
            $this->days[$id] = new StatisticDate(clone $tmp);
            $tmp->modify('+1 day');
        }
    }

    /**
     * @return StatisticDate[]
     */
    public function getDays(): array
    {
        $this->setupDays();

        return array_values($this->days);
    }

    public function getDay(string $year, string $month, string $day): ?StatisticDate
    {
        $this->setupDays();

        if ((int) $month < 10) {
            $month = '0' . $month;
        }

        if ((int) $day < 10) {
            $day = '0' . $day;
        }

        $id = $year . '-' . $month . '-' . $day;

        if (!isset($this->days[$id])) {
            return null;
        }

        return $this->days[$id];
    }

    /**
     * @return DateTime[]
     */
    public function getDateTimes(): array
    {
        $this->setupDays();
        $all = [];

        foreach ($this->days as $id => $day) {
            $all[] = $day->getDate();
        }

        return $all;
    }
}
