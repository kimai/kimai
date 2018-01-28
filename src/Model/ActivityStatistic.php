<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

/**
 * Activity statistics
 */
class ActivityStatistic
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
     * @return ActivityStatistic
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
     * @return ActivityStatistic
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
     * @return ActivityStatistic
     */
    public function setCount($count)
    {
        $this->count = (int) $count;
        return $this;
    }
}
