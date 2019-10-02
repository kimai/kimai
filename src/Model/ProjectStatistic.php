<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Project;

class ProjectStatistic extends TimesheetCountedStatistic
{
    /**
     * @var Project
     */
    private $project;
    /**
     * @var int
     */
    private $activityAmount = 0;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return int
     */
    public function getActivityAmount()
    {
        return $this->activityAmount;
    }

    /**
     * @param int $activityAmount
     * @return ProjectStatistic
     */
    public function setActivityAmount($activityAmount)
    {
        $this->activityAmount = (int) $activityAmount;

        return $this;
    }
}
