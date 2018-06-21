<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

/**
 * Yearly statistics
 */
class Month
{
    /**
     * @var string
     */
    protected $month;
    /**
     * @var int
     */
    protected $totalDuration = 0;
    /**
     * @var int
     */
    protected $totalRate = 0;

    /**
     * Month constructor.
     * @param string $month
     */
    public function __construct($month)
    {
        $this->month = $month;
    }

    /**
     * @return string
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @return int
     */
    public function getTotalDuration()
    {
        return $this->totalDuration;
    }

    /**
     * @param $totalDuration
     * @return $this
     */
    public function setTotalDuration($totalDuration)
    {
        $this->totalDuration = $totalDuration;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalRate()
    {
        return $this->totalRate;
    }

    /**
     * @param $totalRate
     * @return $this
     */
    public function setTotalRate($totalRate)
    {
        $this->totalRate = $totalRate;

        return $this;
    }
}
