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
    private $activityAmount = 0;
    /**
     * @var int
     */
    private $projectAmount = 0;

    public function getActivityAmount(): int
    {
        return $this->activityAmount;
    }

    public function setActivityAmount(int $activityAmount): CustomerStatistic
    {
        $this->activityAmount = $activityAmount;

        return $this;
    }

    public function getProjectAmount(): int
    {
        return $this->projectAmount;
    }

    public function setProjectAmount(int $projectAmount): CustomerStatistic
    {
        $this->projectAmount = $projectAmount;

        return $this;
    }
}
