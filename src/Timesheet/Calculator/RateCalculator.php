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
        if (null === $record->getEnd()) {
            return;
        }

        $fixedRate = $this->findFixedRate($record);
        if (null !== $fixedRate) {
            $record->setRate($fixedRate);
            return;
        }

        $hourlyRate = $this->findHourlyRate($record);
        $factor = $this->getRateFactor($record);

        $record->setRate(
            $this->calculateRate($record->getDuration(), $hourlyRate, $factor)
        );
    }


    /**
     * @param Timesheet $record
     * @return float
     */
    protected function findHourlyRate(Timesheet $record)
    {
        if (null !== $record->getHourlyRate()) {
            return $record->getHourlyRate();
        }

        $activity = $record->getActivity();
        if (null !== $activity->getHourlyRate()) {
            return $activity->getHourlyRate();
        }

        $project = $activity->getProject();
        if (null !== $project) {
            if (null !== $project->getHourlyRate()) {
                return $project->getHourlyRate();
            }

            $customer = $project->getCustomer();
            if (null !== $customer->getHourlyRate()) {
                return $customer->getHourlyRate();
            }
        }

        return (float)$record->getUser()->getPreferenceValue(UserPreference::HOURLY_RATE, 0);
    }

    /**
     * @param Timesheet $record
     * @return float|null
     */
    protected function findFixedRate(Timesheet $record)
    {
        if (null !== $record->getFixedRate()) {
            return $record->getFixedRate();
        }

        $activity = $record->getActivity();
        if (null !== $activity->getFixedRate()) {
            return $activity->getFixedRate();
        }

        $project = $activity->getProject();
        if (null !== $project) {
            if (null !== $project->getFixedRate()) {
                return $project->getFixedRate();
            }

            $customer = $project->getCustomer();
            if (null !== $customer->getFixedRate()) {
                return $customer->getFixedRate();
            }
        }

        return null;
    }

    /**
     * @param Timesheet $record
     * @return float
     */
    protected function getRateFactor(Timesheet $record)
    {
        $factor = 0;
        foreach ($this->rates as $rateFactor) {
            $weekday = $record->getEnd()->format('l');
            $days = array_map('strtolower', $rateFactor['days']);
            if (in_array(strtolower($weekday), $days)) {
                if ($rateFactor['factor'] <= 0) {
                    throw new \InvalidArgumentException(
                        'A rate factor smaller or equals 0 is not allowed, given: ' . $rateFactor['factor']
                    );
                }
                $factor += $rateFactor['factor'];
            }
        }

        if ($factor <= 0) {
            $factor = 1;
        }

        return $factor;
    }

    /**
     * @param int $duration
     * @param float $hourlyRate
     * @param float $factor
     * @return float
     */
    protected function calculateRate($duration, $hourlyRate, $factor)
    {
        return (float) $hourlyRate * ($duration / 3600) * $factor;
    }
}
