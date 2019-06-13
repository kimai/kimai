<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class ProjectStatistic extends TimesheetCountedStatistic
{
    /**
     * @var int
     */
    protected $activityAmount = 0;

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
