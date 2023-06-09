<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use DateTimeInterface;

class Month
{
    /**
     * @var Day[]
     */
    private array $days = [];

    public function __construct(private \DateTimeInterface $month)
    {
        $date = new \DateTimeImmutable($this->month->format('Y-m-01 00:00:00'));
        $start = $date->format('m');
        while ($start === $date->format('m')) {
            $day = $this->createDay($date);
            $this->setDay($day);
            $date = $date->add(new \DateInterval('P1D'));
        }
    }

    protected function createDay(\DateTimeInterface $day): Day
    {
        return new Day($day);
    }

    public function getMonth(): DateTimeInterface
    {
        return $this->month;
    }

    protected function setDay(Day $day): void
    {
        $this->days['_' . $day->getDay()->format('d')] = $day;
    }

    public function getDay(DateTimeInterface $date): Day
    {
        return $this->days['_' . $date->format('d')];
    }

    /**
     * @return Day[]
     */
    public function getDays(): array
    {
        return array_values($this->days);
    }
}
