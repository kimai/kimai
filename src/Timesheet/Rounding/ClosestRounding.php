<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Rounding;

use App\Entity\Timesheet;

final class ClosestRounding implements RoundingInterface
{
    public function getId(): string
    {
        return 'closest';
    }

    /**
     * @param Timesheet $record
     * @param int $minutes
     */
    public function roundBegin(Timesheet $record, $minutes)
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
        if ($diff > ($seconds / 2)) {
            $newBegin->setTimestamp($timestamp - $diff + $seconds);
        } else {
            $newBegin->setTimestamp($timestamp - $diff);
        }
        $record->setBegin($newBegin);
    }

    /**
     * @param Timesheet $record
     * @param int $minutes
     */
    public function roundEnd(Timesheet $record, $minutes)
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
        if ($diff > ($seconds / 2)) {
            $newEnd->setTimestamp($timestamp - $diff + $seconds);
        } else {
            $newEnd->setTimestamp($timestamp - $diff);
        }
        $record->setEnd($newEnd);
    }

    /**
     * @param Timesheet $record
     * @param int $minutes
     */
    public function roundDuration(Timesheet $record, $minutes)
    {
        if ($minutes <= 0) {
            return;
        }

        $timestamp = $record->getDuration();
        $seconds = $minutes * 60;
        $diff = $timestamp % $seconds;

        if (0 === $diff) {
            return;
        }

        if ($diff > ($seconds / 2)) {
            $record->setDuration($timestamp - $diff + $seconds);
        } else {
            $record->setDuration($timestamp - $diff);
        }
    }
}
