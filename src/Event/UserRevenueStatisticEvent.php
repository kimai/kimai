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
    private RevenueStatisticEvent $event;

    public function __construct(private User $user, ?\DateTimeInterface $begin, ?\DateTimeInterface $end)
    {
        $this->event = new RevenueStatisticEvent($begin, $end);
    }

    public function getBegin(): ?\DateTimeInterface
    {
        return $this->event->getBegin();
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->event->getEnd();
    }

    public function getRevenue(): array
    {
        return $this->event->getRevenue();
    }

    public function addRevenue(string $currency, float $revenue): void
    {
        $this->event->addRevenue($currency, $revenue);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
