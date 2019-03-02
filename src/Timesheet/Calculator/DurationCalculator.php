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
use App\Timesheet\Rounding\RoundingInterface;

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
        if (null === $record->getEnd()) {
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
                $class = 'App\\Timesheet\\Rounding\\' . ucfirst($rounding['mode']) . 'Rounding';
                /* @var $rounder RoundingInterface */
                $rounder = new $class();
                $rounder->roundBegin($record, $rounding['begin']);
                $rounder->roundEnd($record, $rounding['end']);
                $this->applyDuration($record);
                $rounder->roundDuration($record, $rounding['duration']);
            }
        }
    }
}
