<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

/**
 * Customer statistics
 */
class CustomerStatistic
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
     * @var int
     */
    protected $projectAmount = 0;

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
     * @return $this
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
     * @return $this
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
     * @return $this
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
