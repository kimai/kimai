<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\User;
use App\Model\Statistic\Month;

class UserStatistic extends TimesheetCountedStatistic
{
    public function __construct(private User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function addValuesFromMonth(Month $month): void
    {
        $this->setDuration($this->getDuration() + $month->getDuration());
        $this->setDurationBillable($this->getDurationBillable() + $month->getBillableDuration());
        $this->setRate($this->getRate() + $month->getRate());
        $this->setRateBillable($this->getRateBillable() + $month->getBillableRate());
        $this->setInternalRate($this->getInternalRate() + $month->getInternalRate());
    }
}
