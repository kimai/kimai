<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Model\Statistic\BudgetStatistic;

class CustomerStatistic extends BudgetStatistic
{
    /**
     * @var int
     */
    private $activityAmount = 0;
    /**
     * @var int
     */
    private $projectAmount = 0;

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
    public function setActivityAmount(int $activityAmount): CustomerStatistic
    {
        $this->activityAmount = $activityAmount;

        return $this;
    }

    /**
     * @deprecated since 1.15 - will be removed with 2.0
     * @return int
     */
    public function getProjectAmount(): int
    {
        return $this->projectAmount;
    }

    /**
     * @deprecated since 1.15 - will be removed with 2.0
     * @param int $projectAmount
     * @return $this
     */
    public function setProjectAmount(int $projectAmount): CustomerStatistic
    {
        $this->projectAmount = $projectAmount;

        return $this;
    }
}
