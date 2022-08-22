<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;

final class TimesheetEntry implements DragAndDropEntry
{
    public function __construct(private Timesheet $timesheet, private string $color)
    {
    }

    public function getData(): array
    {
        $tags = null;
        if (!empty($this->timesheet->getTagsAsArray())) {
            $tags = implode(',', $this->timesheet->getTagsAsArray());
        }

        return [
            // restarting a timesheet should not copy the description - @version 1.21
            //'description' => $this->timesheet->getDescription(),
            'activity' => $this->timesheet->getActivity()?->getId(),
            'project' => $this->timesheet->getProject()?->getId(),
            'tags' => $tags,
        ];
    }

    public function getTitle(): string
    {
        if ($this->timesheet->getActivity() !== null && $this->timesheet->getActivity()->getName() !== null) {
            return $this->timesheet->getActivity()->getName();
        }

        if (null !== $this->timesheet->getProject() && $this->timesheet->getProject()->getName() !== null) {
            return $this->timesheet->getProject()->getName();
        }

        return $this->timesheet->getDescription() ?? '';
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getBlockName(): ?string
    {
        return 'dd_timesheet';
    }

    public function getActivity(): ?Activity
    {
        return $this->timesheet->getActivity();
    }

    public function getProject(): ?Project
    {
        return $this->timesheet->getProject();
    }
}
