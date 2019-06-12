<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

use DateTime;

class Day
{
    /**
     * @var int
     */
    protected $totalDuration = 0;
    /**
     * @var float
     */
    protected $totalRate = 0.00;
    /**
     * @var DateTime
     */
    protected $day;

    public function __construct(DateTime $day, int $duration, float $rate)
    {
        $this->day = $day;
        $this->totalDuration = $duration;
        $this->totalRate = $rate;
    }

    public function getDay(): DateTime
    {
        return $this->day;
    }

    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    public function setTotalDuration(int $seconds): Day
    {
        $this->totalDuration = $seconds;

        return $this;
    }

    public function getTotalRate(): float
    {
        return $this->totalRate;
    }

    public function setTotalRate(float $totalRate): Day
    {
        $this->totalRate = $totalRate;

        return $this;
    }
}
