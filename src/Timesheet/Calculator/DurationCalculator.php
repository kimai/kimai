<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Calculator;

use App\Entity\Timesheet;
use App\Timesheet\CalculatorInterface;

/**
 * Implementation to calculate the durations for a timesheet record.
 *
 * This calculator takes the configuration %kimai.timesheet.rounding% as argument,
 * so its rounding behaviour can be customized.
 */
class DurationCalculator implements CalculatorInterface
{
    /**
     * @var array
     */
    protected $roundings;

    /**
     * DurationCalculator constructor.
     * @param array $roundings
     */
    public function __construct(array $roundings)
    {
        $this->roundings = $roundings;
    }

    /**
     * @param Timesheet $record
     */
    public function calculate(Timesheet $record)
    {
        if ($record->getEnd() === null) {
            return;
        }

        $this->applyDuration($record);
        $this->applyRoundings($record);
    }

    /**
     * @param Timesheet $record
     */
    protected function applyDuration(Timesheet $record)
    {
        $duration = $record->getEnd()->getTimestamp() - $record->getBegin()->getTimestamp();
        $record->setDuration($duration);
    }

    /**
     * @param Timesheet $record
     */
    protected function applyRoundings(Timesheet $record)
    {
        foreach ($this->roundings as $rounding) {
            $weekday = $record->getEnd()->format('l');
            $days = array_map('strtolower', $rounding['days']);

            if (in_array(strtolower($weekday), $days)) {
                $this->roundBegin($record, $rounding['begin']);
                $this->roundEnd($record, $rounding['end']);
                $this->applyDuration($record);
                $this->roundDuration($record, $rounding['duration']);
            }
        }
    }

    /**
     * @param Timesheet $record
     * @param int $minutes
     */
    protected function roundBegin(Timesheet $record, $minutes)
    {
        if ($minutes <= 0) {
            return;
        }

        $timestamp = $record->getBegin()->getTimestamp();
        $seconds = $minutes * 60;
        $diff = $timestamp % $seconds;

        if ($diff === 0) {
            return;
        }

        $record->getBegin()->setTimestamp($timestamp - $diff);
    }

    /**
     * @param Timesheet $record
     * @param int $minutes
     */
    protected function roundEnd(Timesheet $record, $minutes)
    {
        if ($minutes <= 0) {
            return;
        }

        $timestamp = $record->getEnd()->getTimestamp();
        $seconds = $minutes * 60;
        $diff = $timestamp % $seconds;

        if ($diff === 0) {
            return;
        }

        $record->getEnd()->setTimestamp($timestamp - $diff + $seconds);
    }

    /**
     * @param Timesheet $record
     * @param int $minutes
     */
    protected function roundDuration(Timesheet $record, $minutes)
    {
        if ($minutes <= 0) {
            return;
        }

        $timestamp = $record->getDuration();
        $seconds = $minutes * 60;
        $diff = $timestamp % $seconds;

        if ($diff === 0) {
            return;
        }

        $record->setDuration($timestamp - $diff + $seconds);
    }
}
