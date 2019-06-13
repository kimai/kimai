<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

use InvalidArgumentException;

/**
 * Monthly statistics
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
     * @var float
     */
    protected $totalRate = 0.00;

    /**
     * @param string $month
     */
    public function __construct(string $month)
    {
        $monthNumber = (int) $month;
        if ($monthNumber < 1 || $monthNumber > 12) {
            throw new InvalidArgumentException(
                sprintf('Invalid month given. Expected 1-12, received "%s".', $monthNumber)
            );
        }
        $this->month = $month;
    }

    /**
     * @return string
     */
    public function getMonth()
    {
        return $this->month;
    }

    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    public function setTotalDuration(int $seconds): Month
    {
        $this->totalDuration = $seconds;

        return $this;
    }

    public function getTotalRate(): float
    {
        return $this->totalRate;
    }

    public function setTotalRate(float $totalRate): Month
    {
        $this->totalRate = $totalRate;

        return $this;
    }
}
