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
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function addValuesFromMonth(Month $month): void
    {
        $this->setRecordDuration($this->getRecordDuration() + $month->getTotalDuration());
        $this->setDurationBillable($this->getDurationBillable() + $month->getBillableDuration());
        $this->setRecordRate($this->getRate() + $month->getTotalRate());
        $this->setRateBillable($this->getRateBillable() + $month->getBillableRate());
        $this->setRecordInternalRate($this->getRecordInternalRate() + $month->getTotalInternalRate());
    }
}
