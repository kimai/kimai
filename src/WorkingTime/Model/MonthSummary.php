<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

use App\Model\Month as BaseMonth;

final class MonthSummary extends BaseMonth
{
    private int $expectedTime = 0;
    private int $actualTime = 0;

    public function getExpectedTime(): int
    {
        return $this->expectedTime;
    }

    public function setExpectedTime(int $expectedTime): void
    {
        $this->expectedTime = $expectedTime;
    }

    public function getActualTime(): int
    {
        return $this->actualTime;
    }

    public function setActualTime(int $actualTime): void
    {
        $this->actualTime = $actualTime;
    }
}
