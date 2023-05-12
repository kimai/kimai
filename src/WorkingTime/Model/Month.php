<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

use App\Entity\User;
use App\Model\Month as BaseMonth;

/**
 * @method array<Day> getDays()
 */
final class Month extends BaseMonth
{
    private ?bool $locked = null;
    private ?int $expectedTime = null;
    private ?int $actualTime = null;

    /**
     * A month is only locked IF every day is approved.
     * If there is even one day left open, the entire month is not locked.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        if ($this->locked === null) {
            $this->locked = true;
            foreach ($this->getDays() as $day) {
                if ($day->getWorkingTime() !== null && !$day->getWorkingTime()->isApproved()) {
                    $this->locked = false;
                }
            }
        }

        return $this->locked;
    }

    public function getLockDate(): ?\DateTimeInterface
    {
        foreach ($this->getDays() as $day) {
            if ($day->getWorkingTime() !== null && $day->getWorkingTime()->isApproved()) {
                return $day->getWorkingTime()->getApprovedAt();
            }
        }

        return null;
    }

    public function getLockedBy(): ?User
    {
        foreach ($this->getDays() as $day) {
            if ($day->getWorkingTime() !== null && $day->getWorkingTime()->isApproved()) {
                return $day->getWorkingTime()->getApprovedBy();
            }
        }

        return null;
    }

    protected function createDay(\DateTimeInterface $day): Day
    {
        return new Day($day);
    }

    public function getExpectedTime(\DateTimeInterface $until): int
    {
        if ($this->expectedTime === null) {
            $this->expectedTime = 0;

            foreach ($this->getDays() as $day) {
                if ($until < $day->getDay()) {
                    break;
                }
                if ($day->getWorkingTime() !== null) {
                    $this->expectedTime += $day->getWorkingTime()->getExpectedTime();
                }
            }
        }

        return $this->expectedTime;
    }

    public function getActualTime(): int
    {
        if ($this->actualTime === null) {
            $this->actualTime = 0;

            foreach ($this->getDays() as $day) {
                if ($day->getWorkingTime() !== null) {
                    $this->actualTime += $day->getWorkingTime()->getActualTime();
                }
            }
        }

        return $this->actualTime;
    }
}
