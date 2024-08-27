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
    /**
     * @param int<0, max> $count
     * @param int<0, max> $duration
     */
    public function __construct(private readonly int $count, private readonly int $duration)
    {
    }

    /**
     * @return int<0, max>
     */
    public function getCount(): int
    {
        return $this->count;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
}
