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
    /**
     * @var Timesheet
     */
    private $timesheet;
    /**
     * @var string
     */
    private $color;

    public function __construct(Timesheet $timesheet, string $color)
    {
        $this->timesheet = $timesheet;
        $this->color = $color;
    }

    public function getData(): array
    {
        return [
            'description' => $this->timesheet->getDescription(),
            'activity' => $this->timesheet->getActivity() !== null ? $this->timesheet->getActivity()->getId() : null,
            'project' => $this->timesheet->getProject() !== null ? $this->timesheet->getProject()->getId() : null,
            'tags' => implode(',', $this->timesheet->getTagsAsArray()),
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
