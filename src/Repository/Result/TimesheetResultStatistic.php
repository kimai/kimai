<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Result;

final class TimesheetResultStatistic
{
    public function __construct(private int $count, private int $duration)
    {
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
