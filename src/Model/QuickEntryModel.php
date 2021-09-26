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

class QuickEntryModel
{
    private $project;
    private $activity;
    /** @var Timesheet[] */
    private $timesheets = [];

    public function __construct(?Project $project = null, ?Activity $activity = null)
    {
        $this->project = $project;
        $this->activity = $activity;
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

    public function getTimesheets(): array
    {
        return array_values($this->timesheets);
    }

    public function addTimesheet(Timesheet $timesheet): void
    {
        $day = clone $timesheet->getBegin();
        $this->timesheets[$day->format('Y-m-d')] = $timesheet;
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
}
