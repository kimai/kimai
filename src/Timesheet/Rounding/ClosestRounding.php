<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Rounding;

use App\Entity\Timesheet;

class ClosestRounding implements RoundingInterface
{
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

        if ($diff > ($seconds / 2)) {
            $record->getBegin()->setTimestamp($timestamp - $diff + $seconds);
        } else {
            $record->getBegin()->setTimestamp($timestamp - $diff);
        }
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

        if ($diff > ($seconds / 2)) {
            $record->getEnd()->setTimestamp($timestamp - $diff + $seconds);
        } else {
            $record->getEnd()->setTimestamp($timestamp - $diff);
        }
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
