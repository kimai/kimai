<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Used to display the full revenue information for a certain date-range.
 */
final class RevenueStatisticEvent extends Event
{
    private $begin;
    private $end;
    private $revenue = 0.0;

    public function __construct(?\DateTime $begin, ?\DateTime $end)
    {
        $this->begin = $begin;
        $this->end = $end;
    }

    public function getBegin(): ?\DateTime
    {
        return $this->begin;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function getRevenue(): float
    {
        return $this->revenue;
    }

    public function addRevenue(float $revenue): void
    {
        $this->revenue += $revenue;
    }
}
