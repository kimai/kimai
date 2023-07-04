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

final class WorkingTimeApproveMonthEvent extends Event
{
    public function __construct(private User $user, private Month $month, private \DateTimeInterface $approvalDate, private User $approver)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMonth(): Month
    {
        return $this->month;
    }

    public function getApprovalDate(): \DateTimeInterface
    {
        return $this->approvalDate;
    }

    public function getApprover(): User
    {
        return $this->approver;
    }
}
