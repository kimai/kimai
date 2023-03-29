<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

use App\Entity\WorkingTime;
use App\Model\Day as BaseDay;

final class Day extends BaseDay
{
    private ?WorkingTime $workingTime = null;

    public function getWorkingTime(): ?WorkingTime
    {
        return $this->workingTime;
    }

    public function setWorkingTime(?WorkingTime $workingTime): void
    {
        $this->workingTime = $workingTime;
    }
}
