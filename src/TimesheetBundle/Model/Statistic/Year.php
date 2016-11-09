<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Model\Statistic;

/**
 * Yearly statistics
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class Year
{
    /**
     * @var string
     */
    protected $year;
    /**
     * @var Month[]
     */
    protected $months;

    /**
     * Year constructor.
     * @param string $year
     */
    public function __construct($year)
    {
        $this->year = $year;
    }

    /**
     * @return string
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param Month $month
     * @return $this
     */
    public function setMonth(Month $month)
    {
        $this->months[(int)$month->getMonth()] = $month;

        return $this;
    }

    /**
     * @param $month
     * @return null|Month
     */
    public function getMonth($month)
    {
        if (isset($this->months[$month])) {
            return $this->months[$month];
        }

        return null;
    }

    /**
     * @return Month[]
     */
    public function getMonths()
    {
        return array_values($this->months);
    }
}
