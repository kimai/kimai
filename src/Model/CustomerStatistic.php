<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class CustomerStatistic extends TimesheetCountedStatistic
{
    /**
     * @var int
     */
    protected $activityAmount = 0;
    /**
     * @var int
     */
    protected $projectAmount = 0;

    /**
     * @return int
     */
    public function getActivityAmount()
    {
        return $this->activityAmount;
    }

    /**
     * @param int $activityAmount
     * @return $this
     */
    public function setActivityAmount($activityAmount)
    {
        $this->activityAmount = (int) $activityAmount;

        return $this;
    }

    /**
     * @return int
     */
    public function getProjectAmount()
    {
        return $this->projectAmount;
    }

    /**
     * @param int $projectAmount
     * @return $this
     */
    public function setProjectAmount($projectAmount)
    {
        $this->projectAmount = (int) $projectAmount;

        return $this;
    }
}
