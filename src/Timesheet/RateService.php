<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\RateInterface;
use App\Entity\Timesheet;
use App\Entity\UserPreference;
use App\Repository\TimesheetRepository;

/**
 * Implementation to calculate the rate for a timesheet record.
 */
final class RateService implements RateServiceInterface
{
    public function __construct(private array $rates, private TimesheetRepository $repository)
    {
    }

    public function calculate(Timesheet $record): Rate
    {
        if (null === $record->getEnd()) {
            return new Rate(0.00, 0.00);
        }

        $fixedRate = $record->getFixedRate();
        $hourlyRate = $record->getHourlyRate();
        $fixedInternalRate = null;
        $internalRate = null;

        $rate = $this->getBestFittingRate($record);

        if (null !== $rate) {
            if ($rate->isFixed()) {
                $fixedRate ??= $rate->getRate();
                if (null !== $rate->getInternalRate()) {
                    $fixedInternalRate = $rate->getInternalRate();
                }
            } else {
                $hourlyRate ??= $rate->getRate();
                if (null !== $rate->getInternalRate()) {
                    $internalRate = $rate->getInternalRate();
                }
            }
        }

        if (null !== $fixedRate) {
            if (null === $fixedInternalRate) {
                $fixedInternalRate = (float) $record->getUser()->getPreferenceValue(UserPreference::INTERNAL_RATE, $fixedRate, false);
            }

            return new Rate($fixedRate, $fixedInternalRate, null, $fixedRate);
        }

        // user preferences => fallback if nothing else was configured
        if (null === $hourlyRate) {
            $hourlyRate = (float) $record->getUser()->getPreferenceValue(UserPreference::HOURLY_RATE, 0.00, false);
        }

        if (null === $internalRate) {
            $internalRate = (float) $record->getUser()->getPreferenceValue(UserPreference::INTERNAL_RATE, $hourlyRate, false);
        }

        $factor = 1.00;
        // do not apply once a value was calculated - see https://github.com/kimai/kimai/issues/1988
        if ($record->getFixedRate() === null && $record->getHourlyRate() === null) {
            $factor = $this->getRateFactor($record);
        }

        $factoredHourlyRate = $hourlyRate * $factor;
        $factoredInternalRate = $internalRate * $factor;
        $totalRate = 0;
        $totalInternalRate = 0;

        if (null !== $record->getDuration()) {
            $totalRate = Util::calculateRate($factoredHourlyRate, $record->getDuration());
            $totalInternalRate = Util::calculateRate($factoredInternalRate, $record->getDuration());
        }

        return new Rate($totalRate, $totalInternalRate, $factoredHourlyRate, null);
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

    private function getRateFactor(Timesheet $record): float
    {
        $factor = 0.00;

        foreach ($this->rates as $rateFactor) {
            $weekday = $record->getEnd()->format('l');
            $days = array_map('strtolower', $rateFactor['days']);
            if (\in_array(strtolower($weekday), $days)) {
                $factor += (float) $rateFactor['factor'];
            }
        }

        if ($factor <= 0) {
            $factor = 1.00;
        }

        return $factor;
    }
}
