<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

class Timesheet
{
    private int $totalDuration = 0;
    private float $totalRate = 0.00;
    private float $totalInternalRate = 0.00;

    /**
     * For unified access, used in frontend.
     * @see getDuration()
     */
    public function getValue(): int
    {
        return $this->getDuration();
    }

    /**
     * For unified access, used in frontend.
     * @see getTotalDuration()
     */
    public function getDuration(): int
    {
        return $this->totalDuration;
    }

    public function getTotalDuration(): int
    {
        return $this->getDuration();
    }

    public function setTotalDuration(int $totalDuration): void
    {
        $this->totalDuration = $totalDuration;
    }

    /**
     * For unified access, used in frontend.
     * @see getTotalRate()
     */
    public function getRate(): float
    {
        return $this->totalRate;
    }

    public function getTotalRate(): float
    {
        return $this->getRate();
    }

    public function setTotalRate(float $totalRate): void
    {
        $this->totalRate = $totalRate;
    }

    /**
     * For unified access, used in frontend.
     * @see getTotalInternalRate()
     */
    public function getInternalRate(): float
    {
        return $this->totalInternalRate;
    }

    public function getTotalInternalRate(): float
    {
        return $this->getInternalRate();
    }

    public function setTotalInternalRate(float $totalInternalRate): void
    {
        $this->totalInternalRate = $totalInternalRate;
    }
}
