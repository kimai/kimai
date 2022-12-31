<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Rounding;

use App\Entity\Timesheet;

final class FloorRounding implements RoundingInterface
{
    public function getId(): string
    {
        return 'floor';
    }

    public function roundBegin(Timesheet $record, int $minutes): void
    {
        if ($minutes <= 0) {
            return;
        }

        $timestamp = $record->getBegin()->getTimestamp();
        $seconds = $minutes * 60;
        $diff = $timestamp % $seconds;

        if (0 === $diff) {
            return;
        }

        $newBegin = clone $record->getBegin();
        $newBegin->setTimestamp($timestamp - $diff);
        $record->setBegin($newBegin);
    }

    public function roundEnd(Timesheet $record, int $minutes): void
    {
        if ($minutes <= 0) {
            return;
        }

        $timestamp = $record->getEnd()->getTimestamp();
        $seconds = $minutes * 60;
        $diff = $timestamp % $seconds;

        if (0 === $diff) {
            return;
        }

        $newEnd = clone $record->getEnd();
        $newEnd->setTimestamp($timestamp - $diff);
        $record->setEnd($newEnd);
    }

    public function roundDuration(Timesheet $record, int $minutes): void
    {
        if ($minutes <= 0) {
            return;
        }

        $timestamp = $record->getDuration() ?? 0;
        $seconds = $minutes * 60;
        $diff = $timestamp % $seconds;

        if (0 === $diff) {
            return;
        }

        $record->setDuration($timestamp - $diff);
    }
}
