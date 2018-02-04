<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Calculator;

use App\Entity\Timesheet;
use App\Entity\UserPreference;
use App\Timesheet\CalculatorInterface;

/**
 * Implementation to calculate the rate for a timesheet record.
 */
class RateCalculator implements CalculatorInterface
{
    /**
     * @var array
     */
    protected $rates;

    /**
     * RateCalculator constructor.
     * @param array $rates
     */
    public function __construct(array $rates)
    {
        $this->rates = $rates;
    }

    /**
     * @param Timesheet $record
     */
    public function calculate(Timesheet $record)
    {
        if ($record->getEnd() === null) {
            return;
        }

        $rate = $this->calculateRate($record);
        $rate = $this->applyRateFactor($record, $rate);
        $record->setRate($rate);
    }

    /**
     * @param Timesheet $record
     * @param float $rate
     * @return float
     */
    protected function applyRateFactor(Timesheet $record, $rate)
    {
        foreach ($this->rates as $rateFactor) {
            $weekday = $record->getEnd()->format('l');
            $days = array_map('strtolower', $rateFactor['days']);
            if (in_array(strtolower($weekday), $days)) {
                $rate = $rate * $rateFactor['factor'];
            }
        }

        return $rate;
    }

    /**
     * @param Timesheet $record
     * @return float
     */
    protected function calculateRate(Timesheet $record)
    {
        $hourlyRate = (float) $record->getUser()->getPreferenceValue(UserPreference::HOURLY_RATE, 0);
        return (float) $hourlyRate * ($record->getDuration() / 3600);
    }
}
