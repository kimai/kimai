<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

/**
 * Timesheet statistics for all user.
 */
class TimesheetGlobalStatistic extends TimesheetStatistic
{
    /**
     * @var int
     */
    protected $activeThisMonth = 0;
    /**
     * @var int
     */
    protected $activeTotal = 0;
    /**
     * @var int
     */
    protected $activeCurrently = 0;

    /**
     * @return int
     */
    public function getActiveCurrently()
    {
        return $this->activeCurrently;
    }

    /**
     * @param int $activeCurrently
     */
    public function setActiveCurrently($activeCurrently)
    {
        $this->activeCurrently = $activeCurrently;
    }

    /**
     * @return int
     */
    public function getActiveThisMonth()
    {
        return $this->activeThisMonth;
    }

    /**
     * @param int $activeThisMonth
     */
    public function setActiveThisMonth($activeThisMonth)
    {
        $this->activeThisMonth = $activeThisMonth;
    }

    /**
     * @return int
     */
    public function getActiveTotal()
    {
        return $this->activeTotal;
    }

    /**
     * @param int $activeTotal
     */
    public function setActiveTotal($activeTotal)
    {
        $this->activeTotal = $activeTotal;
    }
}
