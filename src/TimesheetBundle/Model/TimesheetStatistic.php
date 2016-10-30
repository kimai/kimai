<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Model;

/**
 * Timesheet statistics
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
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
        $this->durationThisMonth = $durationThisMonth;
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
        $this->amountTotal = $amountTotal;
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
        $this->durationTotal = $durationTotal;
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
        $this->amountThisMonth = $amountThisMonth;
    }
}
