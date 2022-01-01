<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Model\Statistic\BudgetStatistic;

class ProjectStatistic extends BudgetStatistic
{
    /**
     * @var int
     */
    private $activityAmount = 0;

    /**
     * @deprecated since 1.15 - will be removed with 2.0
     * @return int
     */
    public function getActivityAmount(): int
    {
        return $this->activityAmount;
    }

    /**
     * @deprecated since 1.15 - will be removed with 2.0
     * @param int $activityAmount
     * @return $this
     */
    public function setActivityAmount(int $activityAmount): ProjectStatistic
    {
        $this->activityAmount = $activityAmount;

        return $this;
    }
}
