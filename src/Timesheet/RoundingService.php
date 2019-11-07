<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\Timesheet;
use App\Timesheet\Rounding\RoundingInterface;

/**
 * This service takes the configuration %kimai.timesheet.rounding% as argument,
 * so its rounding behaviour can be customized.
 */
final class RoundingService
{
    /**
     * @var array
     */
    private $roundings;

    public function __construct(array $roundings)
    {
        $this->roundings = $roundings;
    }

    public function roundBegin(Timesheet $record): void
    {
        foreach ($this->roundings as $rounding) {
            $weekday = $record->getBegin()->format('l');
            $days = array_map('strtolower', $rounding['days']);

            if (in_array(strtolower($weekday), $days)) {
                $rounder = $this->createRounder($rounding['mode']);
                $rounder->roundBegin($record, $rounding['begin']);
            }
        }
    }

    public function roundEnd(Timesheet $record): void
    {
        foreach ($this->roundings as $rounding) {
            $weekday = $record->getEnd()->format('l');
            $days = array_map('strtolower', $rounding['days']);

            if (in_array(strtolower($weekday), $days)) {
                $rounder = $this->createRounder($rounding['mode']);
                $rounder->roundEnd($record, $rounding['end']);
            }
        }
    }

    public function roundDuration(Timesheet $record): void
    {
        foreach ($this->roundings as $rounding) {
            $weekday = $record->getEnd()->format('l');
            $days = array_map('strtolower', $rounding['days']);

            if (in_array(strtolower($weekday), $days)) {
                $rounder = $this->createRounder($rounding['mode']);
                $rounder->roundDuration($record, $rounding['duration']);
            }
        }
    }

    public function applyRoundings(Timesheet $record): void
    {
        foreach ($this->roundings as $rounding) {
            $weekday = $record->getEnd()->format('l');
            $days = array_map('strtolower', $rounding['days']);

            if (in_array(strtolower($weekday), $days)) {
                $rounder = $this->createRounder($rounding['mode']);
                $rounder->roundBegin($record, $rounding['begin']);
                $rounder->roundEnd($record, $rounding['end']);

                $duration = $record->getEnd()->getTimestamp() - $record->getBegin()->getTimestamp();
                $record->setDuration($duration);

                $rounder->roundDuration($record, $rounding['duration']);
            }
        }
    }

    private function createRounder(string $mode): RoundingInterface
    {
        $class = 'App\\Timesheet\\Rounding\\' . ucfirst($mode) . 'Rounding';
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Unknown rounding mode %s. Missing class: %s', $mode, $class));
        }

        $rounder = new $class();
        if (!$rounder instanceof RoundingInterface) {
            throw new \InvalidArgumentException(sprintf('Invalid rounding mode %s is expected to implement: %s', $mode, RoundingInterface::class));
        }

        return $rounder;
    }
}
