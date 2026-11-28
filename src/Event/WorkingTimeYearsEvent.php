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
 * Dispatched once, when the working times of several users are loaded at the same time.
 *
 * Afterwards a WorkingTimeYearEvent is dispatched for every single year.
 * Listeners can use this event to preload their data for all users at once,
 * instead of querying it for every user in WorkingTimeYearEvent.
 */
final class WorkingTimeYearsEvent extends Event
{
    /**
     * @param array<Year> $years
     */
    public function __construct(private readonly array $years, private readonly \DateTimeInterface $until)
    {
    }

    /**
     * @return array<Year>
     */
    public function getYears(): array
    {
        return $this->years;
    }

    public function getUntil(): \DateTimeInterface
    {
        return $this->until;
    }
}
