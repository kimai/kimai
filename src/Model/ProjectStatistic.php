<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

/**
 * Project statistics
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectStatistic
{
    /**
     * @var int
     */
    protected $count = 0;
    /**
     * @var int
     */
    protected $recordAmount = 0;
    /**
     * @var int
     */
    protected $recordDuration = 0;
    /**
     * @var int
     */
    protected $activityAmount = 0;

    /**
     * Returns the total amount of included timesheet records.
     *
     * @return int
     */
    public function getRecordAmount()
    {
        return $this->recordAmount;
    }

    /**
     * @param int $recordAmount
     * @return ProjectStatistic
     */
    public function setRecordAmount($recordAmount)
    {
        $this->recordAmount = (int) $recordAmount;
        return $this;
    }

    /**
     * Returns the total duration of all included timesheet records.
     *
     * @return int
     */
    public function getRecordDuration()
    {
        return $this->recordDuration;
    }

    /**
     * @param int $recordDuration
     * @return ProjectStatistic
     */
    public function setRecordDuration($recordDuration)
    {
        $this->recordDuration = (int) $recordDuration;
        return $this;
    }

    /**
     * Returns the amount of activities that are included in the statistic result.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return ProjectStatistic
     */
    public function setCount($count)
    {
        $this->count = (int) $count;
        return $this;
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
