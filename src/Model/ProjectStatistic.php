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
    private $activityAmount = 0;

    public function getActivityAmount(): int
    {
        return $this->activityAmount;
    }

    public function setActivityAmount(int $activityAmount): ProjectStatistic
    {
        $this->activityAmount = $activityAmount;

        return $this;
    }
}
