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
use App\Timesheet\RoundingService;

/**
 * Implementation to calculate the durations for a timesheet record.
 */
final class DurationCalculator implements CalculatorInterface
{
    /**
     * @var RoundingService
     */
    private $roundings;

    public function __construct(RoundingService $roundings)
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

        $duration = $record->getEnd()->getTimestamp() - $record->getBegin()->getTimestamp();
        $record->setDuration($duration);

        $this->roundings->applyRoundings($record);
    }
}
