<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use DateTime;

/**
 * Timesheet statistics for one user.
 */
class TimesheetStatistic
{
    /**
     * @var int
     */
    protected $durationThisMonth = 0;
    /**
     * @var int
     */
    protected $durationTotal = 0;
    /**
     * @var int
     */
    protected $amountThisMonth = 0;
    /**
     * @var int
     */
    protected $amountTotal = 0;
    /**
     * @var \DateTime
     */
    protected $firstEntry;
    /**
     * @var int
     */
    protected $recordsTotal = 0;

    /**
     * @return int
     */
    public function getDurationThisMonth()
    {
        return $this->durationThisMonth;
    }

    /**
     * @param int $durationThisMonth
     */
    public function setDurationThisMonth($durationThisMonth)
    {
        $this->durationThisMonth = (int) $durationThisMonth;
    }

    /**
     * @return int
     */
    public function getAmountTotal()
    {
        return $this->amountTotal;
    }

    /**
     * @param int $amountTotal
     */
    public function setAmountTotal($amountTotal)
    {
        $this->amountTotal = (int) $amountTotal;
    }

    /**
     * @return int
     */
    public function getDurationTotal()
    {
        return $this->durationTotal;
    }

    /**
     * @param int $durationTotal
     */
    public function setDurationTotal($durationTotal)
    {
        $this->durationTotal = (int) $durationTotal;
    }

    /**
     * @return int
     */
    public function getAmountThisMonth()
    {
        return $this->amountThisMonth;
    }

    /**
     * @param int $amountThisMonth
     */
    public function setAmountThisMonth($amountThisMonth)
    {
        $this->amountThisMonth = (int) $amountThisMonth;
    }

    /**
     * @return DateTime
     */
    public function getFirstEntry()
    {
        return $this->firstEntry;
    }

    /**
     * @param DateTime $firstEntry
     */
    public function setFirstEntry(DateTime $firstEntry)
    {
        $this->firstEntry = $firstEntry;
    }

    /**
     * @return int
     */
    public function getRecordsTotal(): int
    {
        return $this->recordsTotal;
    }

    /**
     * @param int $recordsTotal
     * @return TimesheetStatistic
     */
    public function setRecordsTotal(int $recordsTotal)
    {
        $this->recordsTotal = $recordsTotal;
        return $this;
    }
}
