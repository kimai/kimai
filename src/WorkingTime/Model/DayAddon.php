<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

final class DayAddon
{
    private bool $billable = true;

    public function __construct(private string $title, private int $duration, private int $visibleDuration)
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function isBillable(): bool
    {
        return $this->billable;
    }

    public function setBillable(bool $billable): void
    {
        $this->billable = $billable;
    }

    public function getVisibleDuration(): int
    {
        return $this->visibleDuration;
    }
}
