<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

final class DateRange
{
    /**
     * @var array<string, StatisticDate>
     */
    private array $days = [];

    public function __construct(\DateTimeInterface $begin, \DateTimeInterface $end)
    {
        if ($end < $begin) {
            throw new \InvalidArgumentException('End must be later than begin');
        }

        $begin = \DateTimeImmutable::createFromInterface($begin);
        $begin = $begin->setTime(0, 0, 0);

        $end = \DateTimeImmutable::createFromInterface($end);
        $end = $end->setTime(23, 59, 59);

        do {
            $this->days[$begin->format('Y-m-d')] = new StatisticDate($begin);
            $begin = $begin->modify('+1 day');
        } while ($begin <= $end);
    }

    /**
     * @return array<StatisticDate>
     */
    public function getDays(): array
    {
        return array_values($this->days);
    }

    public function setDate(StatisticDate $date): void
    {
        $key = $date->getDate()->format('Y-m-d');
        if (!\array_key_exists($key, $this->days)) {
            throw new \InvalidArgumentException('Unknown date given');
        }

        $this->days[$key] = $date;
    }
}
