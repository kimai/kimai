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

        $record->getBegin()->setTimestamp($timestamp - $diff);
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

        $record->getEnd()->setTimestamp($timestamp - $diff);
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

        $record->setDuration($timestamp - $diff);
    }
}
