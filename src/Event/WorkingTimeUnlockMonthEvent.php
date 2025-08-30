<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use App\WorkingTime\Model\Month;
use Symfony\Contracts\EventDispatcher\Event;

final class WorkingTimeUnlockMonthEvent extends Event
{
    public function __construct(
        private readonly Month $month,
        private readonly User $unlockedBy
    )
    {
    }

    public function getMonth(): Month
    {
        return $this->month;
    }

    public function getUnlockedBy(): User
    {
        return $this->unlockedBy;
    }
}
