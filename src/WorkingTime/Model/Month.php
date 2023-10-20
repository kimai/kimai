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
 * @method Day getDay(\DateTimeInterface $date)
 */
final class Month extends BaseMonth
{
    public function __construct(\DateTimeImmutable $month, private User $user)
    {
        parent::__construct($month);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * A month is only locked IF every day is approved.
     * If there is even one day left open, the entire month is not locked.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        foreach ($this->getDays() as $day) {
            if (!$day->isLocked()) {
                return false;
            }
        }

        return true;
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

    protected function createDay(\DateTimeImmutable $day): Day
    {
        return new Day($day);
    }

    public function getExpectedTime(?\DateTimeInterface $until = null): int
    {
        $time = 0;

        foreach ($this->getDays() as $day) {
            if ($until !== null && $until < $day->getDay()) {
                break;
            }
            if ($day->getWorkingTime() !== null) {
                $time += $day->getWorkingTime()->getExpectedTime();
            }
        }

        return $time;
    }

    public function getActualTime(): int
    {
        $time = 0;

        foreach ($this->getDays() as $day) {
            if ($day->getWorkingTime() !== null) {
                $time += $day->getWorkingTime()->getActualTime();
            }
        }

        return $time;
    }
}
