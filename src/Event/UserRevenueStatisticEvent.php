<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Used to display the full revenue information for a certain date-range and user.
 */
final class UserRevenueStatisticEvent extends Event
{
    private $event;
    private $user;

    public function __construct(User $user, ?\DateTime $begin, ?\DateTime $end)
    {
        $this->user = $user;
        $this->event = new RevenueStatisticEvent($begin, $end);
    }

    public function getBegin(): ?\DateTime
    {
        return $this->event->getBegin();
    }

    public function getEnd(): ?\DateTime
    {
        return $this->event->getEnd();
    }

    public function getRevenue(): float
    {
        return $this->event->getRevenue();
    }

    public function addRevenue(float $revenue): void
    {
        $this->event->addRevenue($revenue);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
