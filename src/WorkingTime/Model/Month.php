<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

use App\Model\Month as BaseMonth;

/**
 * @method getDays() array<Day>
 */
final class Month extends BaseMonth
{
    private bool $locked = false;

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    protected function createDay(\DateTimeInterface $day): Day
    {
        return new Day($day);
    }

    public function getExpectedTime(): int
    {
        $time = 0;

        /** @var Day $day */
        foreach ($this->getDays() as $day) {
            if ($day->getWorkingTime() !== null) {
                $time += $day->getWorkingTime()->getExpectedTime();
            }
        }

        return $time;
    }

    public function getActualTime(): int
    {
        $time = 0;

        /** @var Day $day */
        foreach ($this->getDays() as $day) {
            if ($day->getWorkingTime() !== null) {
                $time += $day->getWorkingTime()->getActualTime();
            }
        }

        return $time;
    }
}
