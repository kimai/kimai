<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Calculator;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Timesheet\CalculatorInterface;
use App\Timesheet\RateServiceInterface;

final class RateResetCalculator implements CalculatorInterface
{
    private const RATE_COMPARISON_EPSILON = 0.000001;

    public function __construct(private readonly RateServiceInterface $rateService)
    {
    }

    public function calculate(Timesheet $record, array $changeset): void
    {
        // check if the rate was changed manually
        foreach (['hourlyRate', 'fixedRate', 'internalRate', 'rate'] as $field) {
            if (\array_key_exists($field, $changeset)) {
                return;
            }
        }

        // if no manual rate changed was applied:
        // check if a field changed, that is relevant for the rate calculation
        // reset all rates, because most users do not even see their rates and would not be able
        // to change the rate, even if they knew that the changed project has another base rate
        $changedFields = array_intersect(['project', 'activity', 'user'], array_keys($changeset));
        if (empty($changedFields)) {
            return;
        }

        $fixedRate = $record->getFixedRate();
        $hourlyRate = $record->getHourlyRate();
        $previousRecord = clone $record;

        if (\in_array('activity', $changedFields, true)) {
            /** @var Activity|null $activity */
            $activity = $changeset['activity'][0];
            $previousRecord->setActivity($activity);
        }

        if (\in_array('project', $changedFields, true)) {
            /** @var Project|null $project */
            $project = $changeset['project'][0];
            $previousRecord->setProject($project);
        }

        if (\in_array('user', $changedFields, true)) {
            /** @var User|null $user */
            $user = $changeset['user'][0];
            $previousRecord->setUser($user);
        }

        $previousRecord->resetRates();
        $previousRate = $this->rateService->calculate($previousRecord);

        $manualFixedRate = null;
        $manualHourlyRate = null;

        if (null !== $fixedRate && !$this->ratesAreEqual($fixedRate, $previousRate->getFixedRate())) {
            $manualFixedRate = $fixedRate;
        } elseif (null !== $hourlyRate && !$this->ratesAreEqual($hourlyRate, $previousRate->getHourlyRate())) {
            $manualHourlyRate = $hourlyRate;
        }

        $record->resetRates();
        $record->setFixedRate($manualFixedRate);
        $record->setHourlyRate($manualHourlyRate);
    }

    private function ratesAreEqual(float $storedRate, ?float $calculatedRate): bool
    {
        return null !== $calculatedRate && abs($storedRate - $calculatedRate) < self::RATE_COMPARISON_EPSILON;
    }

    public function getPriority(): int
    {
        // needs to run before all other
        return 50;
    }
}
