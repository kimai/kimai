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
    /**
     * @var array<string, float>
     */
    private array $revenue = [];

    public function __construct(private ?\DateTimeInterface $begin, private ?\DateTimeInterface $end)
    {
    }

    public function getBegin(): ?\DateTimeInterface
    {
        return $this->begin;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function getRevenue(): array
    {
        return $this->revenue;
    }

    public function addRevenue(string $currency, float $revenue): void
    {
        if (!\array_key_exists($currency, $this->revenue)) {
            $this->revenue[$currency] = 0.0;
        }

        $this->revenue[$currency] += $revenue;
    }
}
