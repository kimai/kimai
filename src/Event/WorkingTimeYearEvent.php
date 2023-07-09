<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\WorkingTime\Model\Year;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Working time for every day of the given year.
 * Will be reflected in the working-time summary row.
 */
final class WorkingTimeYearEvent extends Event
{
    public function __construct(private Year $year, private \DateTimeInterface $until)
    {
    }

    public function getUntil(): \DateTimeInterface
    {
        return $this->until;
    }

    public function getYear(): Year
    {
        return $this->year;
    }
}
