<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;

/**
 * @internal
 */
class QuickEntryModel
{
    private bool $prototype = false;
    /**
     * @var Timesheet[]
     */
    private array $timesheets = [];

    public function __construct(private User $user, private ?Project $project = null, private ?Activity $activity = null)
    {
    }

    public function markAsPrototype(): void
    {
        $this->prototype = true;
    }

    public function isPrototype(): bool
    {
        return $this->prototype;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): void
    {
        $this->project = $project;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): void
    {
        $this->activity = $activity;
    }

    public function hasExistingTimesheet(): bool
    {
        foreach ($this->timesheets as $timesheet) {
            if ($timesheet->getId() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Timesheet[]
     */
    public function getNewTimesheet(): array
    {
        $new = [];

        foreach ($this->timesheets as $timesheet) {
            if ($timesheet->getId() === null && $timesheet->getDuration(false) !== null) {
                $new[] = $timesheet;
            }
        }

        return $new;
    }

    public function hasNewTimesheet(): bool
    {
        return \count($this->getNewTimesheet()) > 0;
    }

    public function hasTimesheetWithDuration(): bool
    {
        foreach ($this->timesheets as $timesheet) {
            if ($timesheet->getDuration(false) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Timesheet[]
     */
    public function getTimesheets(): array
    {
        return $this->timesheets;
    }

    public function addTimesheet(Timesheet $timesheet): void
    {
        $this->timesheets[] = $timesheet;
    }

    /**
     * @param Timesheet[] $timesheets
     */
    public function setTimesheets(array $timesheets): void
    {
        $this->timesheets = [];
        foreach ($timesheets as $timesheet) {
            $this->addTimesheet($timesheet);
        }
    }

    public function getLatestEntry(): ?Timesheet
    {
        $latest = null;

        foreach ($this->timesheets as $timesheet) {
            if ($timesheet->getBegin() === null) {
                continue;
            }

            if ($latest === null) {
                $latest = $timesheet;
                continue;
            }

            if ($latest->getBegin() < $timesheet->getBegin()) {
                $latest = $timesheet;
            }
        }

        return $latest;
    }

    public function getFirstEntry(): ?Timesheet
    {
        $first = null;

        foreach ($this->timesheets as $timesheet) {
            if ($timesheet->getBegin() === null) {
                continue;
            }

            if ($first === null) {
                $first = $timesheet;
                continue;
            }

            if ($first->getBegin() > $timesheet->getBegin()) {
                $first = $timesheet;
            }
        }

        return $first;
    }

    public function __clone()
    {
        $records = $this->timesheets;
        $this->timesheets = [];

        foreach ($records as $record) {
            $this->timesheets[] = clone $record;
        }
    }
}
