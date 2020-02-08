<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Calculator;

use App\Entity\Rate;
use App\Entity\Timesheet;
use App\Entity\UserPreference;
use App\Repository\RateRepository;
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
     * @var RateRepository
     */
    private $repository;

    public function __construct(array $rates, RateRepository $repository)
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

            return;
        }

        $fixedRate = $record->getFixedRate();
        $hourlyRate = $record->getHourlyRate();

        if (null === $fixedRate && null === $hourlyRate) {
            $rate = $this->getBestFittingRate($record);

            if (null !== $rate) {
                if ($rate->isFixed()) {
                    $fixedRate = $rate->getRate();
                } else {
                    $hourlyRate = $rate->getRate();
                }
            }
        }

        if (null !== $fixedRate) {
            $record->setRate($fixedRate);
            $record->setFixedRate($fixedRate);

            return;
        }

        if (null === $hourlyRate) {
            $hourlyRate = (float) $record->getUser()->getPreferenceValue(UserPreference::HOURLY_RATE, 0.00);
        }

        $factor = $this->getRateFactor($record);

        $hourlyRate = (float) ($hourlyRate * $factor);
        $totalRate = 0;
        if (null !== $record->getDuration()) {
            $totalRate = Util::calculateRate($hourlyRate, $record->getDuration());
        }

        $record->setHourlyRate($hourlyRate);
        $record->setRate($totalRate);
    }

    private function getBestFittingRate(Timesheet $timesheet): ?Rate
    {
        $rates = $this->repository->findMatchingRates($timesheet);
        /** @var Rate[] $sorted */
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
            if (in_array(strtolower($weekday), $days)) {
                $factor += $rateFactor['factor'];
            }
        }

        if ($factor <= 0) {
            $factor = 1;
        }

        return $factor;
    }
}
