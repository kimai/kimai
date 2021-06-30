<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectDetails;

use App\Entity\Project;
use App\Model\ActivityStatistic;
use App\Model\Statistic\Year;

final class ProjectDetailsModel
{
    /**
     * @var Project
     */
    private $project;
    /**
     * @var Year[]
     */
    private $years;
    /**
     * @var array
     */
    private $yearlyActivities = [];
    /**
     * @var ActivityStatistic[]
     */
    private $activities = [];

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function addYearActivity(string $year, array $activityStatistic): void
    {
        $this->yearlyActivities[$year][] = $activityStatistic;
    }

    public function addActivity(ActivityStatistic $activityStatistic): void
    {
        $this->activities[] = $activityStatistic;
    }

    /**
     * @return ActivityStatistic[]
     */
    public function getActivities(): array
    {
        return $this->activities;
    }

    public function getYearActivities(string $year): ?array
    {
        if (!isset($this->yearlyActivities[$year])) {
            return null;
        }

        return $this->yearlyActivities[$year];
    }

    /**
     * @return Year[]
     */
    public function getYears(): array
    {
        return $this->years;
    }

    public function getYear(string $year): ?Year
    {
        foreach ($this->years as $tmp) {
            if ($tmp->getYear() === $year) {
                return $tmp;
            }
        }

        return null;
    }

    /**
     * @param Year[] $years
     */
    public function setYears(array $years): void
    {
        $this->years = $years;
    }
}
