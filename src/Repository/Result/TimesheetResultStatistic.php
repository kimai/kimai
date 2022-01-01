<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Result;

class TimesheetResultStatistic
{
    private $count = 0;
    private $duration = 0;

    public function __construct(int $count, int $duration)
    {
        $this->count = $count;
        $this->duration = $duration;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
}
