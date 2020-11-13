<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Calculator;

use App\Entity\Rate;
use App\Entity\RateInterface;
use App\Entity\Timesheet;
use App\Entity\UserPreference;
use App\Repository\TimesheetRepository;
use App\Timesheet\CalculatorInterface;
use App\Timesheet\Util;

/**
 * Implementation to calculate the rate for a timesheet record.
 */
class RateCalculator implements CalculatorInterface
{
    /**
     * @var array
     */
    private $rates;
    /**
     * @var TimesheetRepository
     */
    private $repository;

    public function __construct(array $rates, TimesheetRepository $repository)
    {
        $this->rates = $rates;
        $this->repository = $repository;
    }

    /**
     * @param Timesheet $record
     */
    public function calculate(Timesheet $record)
    {
        if (null === $record->getEnd()) {
            $record->setRate(0);
            $record->setInternalRate(0);

            return;
        }

        $fixedRate = $record->getFixedRate();
        $hourlyRate = $record->getHourlyRate();
        $fixedInternalRate = null;
        $internalRate = null;

        $rate = $this->getBestFittingRate($record);

        if (null !== $rate) {
            if ($rate->isFixed()) {
                $fixedRate = $fixedRate ?? $rate->getRate();
                $fixedInternalRate = $rate->getRate();
                if (null !== $rate->getInternalRate()) {
                    $fixedInternalRate = $rate->getInternalRate();
                }
            } else {
                $hourlyRate = $hourlyRate ?? $rate->getRate();
                $internalRate = $rate->getRate();
                if (null !== $rate->getInternalRate()) {
                    $internalRate = $rate->getInternalRate();
                }
            }
        }

        if (null !== $fixedRate) {
            $record->setFixedRate($fixedRate);
            $record->setRate($fixedRate);
            if (null === $fixedInternalRate) {
                $fixedInternalRate = (float) $record->getUser()->getPreferenceValue(UserPreference::INTERNAL_RATE, $fixedRate);
            }
            $record->setInternalRate($fixedInternalRate);

            return;
        }

        // user preferences => fallback if nothing else was configured
        if (null === $hourlyRate) {
            $hourlyRate = (float) $record->getUser()->getPreferenceValue(UserPreference::HOURLY_RATE, 0.00);
        }
        if (null === $internalRate) {
            $internalRate = $record->getUser()->getPreferenceValue(UserPreference::INTERNAL_RATE, 0.00);
            if (null === $internalRate) {
                $internalRate = $hourlyRate;
            } else {
                $internalRate = (float) $internalRate;
            }
        }

        $factor = $this->getRateFactor($record);

        $factoredHourlyRate = (float) ($hourlyRate * $factor);
        $factoredInternalRate = (float) ($internalRate * $factor);
        $totalRate = 0;
        $totalInternalRate = 0;
        if (null !== $record->getDuration()) {
            $totalRate = Util::calculateRate($factoredHourlyRate, $record->getDuration());
            $totalInternalRate = Util::calculateRate($factoredInternalRate, $record->getDuration());
        }
        $record->setHourlyRate($factoredHourlyRate);
        $record->setInternalRate($totalInternalRate);
        $record->setRate($totalRate);
    }

    private function getBestFittingRate(Timesheet $timesheet): ?RateInterface
    {
        $rates = $this->repository->findMatchingRates($timesheet);
        /** @var RateInterface[] $sorted */
        $sorted = [];
        foreach ($rates as $rate) {
            $score = $rate->getScore();
            if (null !== $rate->getUser() && $timesheet->getUser() === $rate->getUser()) {
                ++$score;
            }

            $sorted[$score] = $rate;
        }

        if (!empty($sorted)) {
            ksort($sorted);

            return end($sorted);
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
            if (\in_array(strtolower($weekday), $days)) {
                $factor += $rateFactor['factor'];
            }
        }

        if ($factor <= 0) {
            $factor = 1;
        }

        return $factor;
    }
}
