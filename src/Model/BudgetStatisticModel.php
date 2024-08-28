<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\EntityWithBudget;
use App\Model\Statistic\BudgetStatistic;

/**
 * Object used to unify the access to budget data in charts.
 *
 * @internal do not use in plugins, no BC promise given!
 */
class BudgetStatisticModel implements BudgetStatisticModelInterface
{
    private ?BudgetStatistic $statistic = null;
    private ?BudgetStatistic $statisticTotal = null;

    public function __construct(private readonly EntityWithBudget $entity)
    {
    }

    public function getEntity(): EntityWithBudget
    {
        return $this->entity;
    }

    public function getStatistic(): ?BudgetStatistic
    {
        return $this->statistic;
    }

    public function setStatistic(BudgetStatistic $statistic): void
    {
        $this->statistic = $statistic;
    }

    public function getStatisticTotal(): ?BudgetStatistic
    {
        return $this->statisticTotal;
    }

    public function setStatisticTotal(BudgetStatistic $statistic): void
    {
        $this->statisticTotal = $statistic;
    }

    public function hasTimeBudget(): bool
    {
        return $this->entity->hasTimeBudget();
    }

    public function getTimeBudget(): int
    {
        return $this->entity->getTimeBudget();
    }

    public function isMonthlyBudget(): bool
    {
        return $this->entity->isMonthlyBudget();
    }

    public function getDurationBillable(): int
    {
        if ($this->isMonthlyBudget()) {
            return $this->getDurationBillableRelative();
        }

        return $this->getDurationBillableTotal();
    }

    public function getDurationBillableRelative(): int
    {
        if ($this->statistic === null) {
            return 0;
        }

        return $this->statistic->getDurationBillable();
    }

    public function getDurationBillableTotal(): int
    {
        if ($this->statisticTotal === null) {
            return 0;
        }

        return $this->statisticTotal->getDurationBillable();
    }

    public function getTimeBudgetOpen(): int
    {
        $value = $this->getTimeBudget() - $this->getDurationBillable();

        return max($value, 0);
    }

    public function getTimeBudgetOpenRelative(): int
    {
        $value = $this->getTimeBudget() - $this->getDurationBillableRelative();

        return max($value, 0);
    }

    public function getTimeBudgetSpent(): int
    {
        return $this->getDurationBillable();
    }

    public function hasBudget(): bool
    {
        return $this->entity->hasBudget();
    }

    public function getBudget(): float
    {
        return $this->entity->getBudget();
    }

    public function getBudgetOpen(): float
    {
        $value = $this->getBudget() - $this->getRateBillable();

        return max($value, 0);
    }

    public function getBudgetOpenRelative(): float
    {
        $value = $this->getBudget() - $this->getRateBillableRelative();

        return max($value, 0);
    }

    public function getBudgetSpent(): float
    {
        return $this->getRateBillable();
    }

    public function getRateBillable(): float
    {
        if ($this->isMonthlyBudget()) {
            return $this->getRateBillableRelative();
        }

        return $this->getRateBillableTotal();
    }

    public function getRateBillableRelative(): float
    {
        if ($this->statistic === null) {
            return 0.00;
        }

        return $this->statistic->getRateBillable();
    }

    public function getRateBillableTotal(): float
    {
        if ($this->statisticTotal === null) {
            return 0.00;
        }

        return $this->statisticTotal->getRateBillable();
    }

    public function getRate(): float
    {
        if ($this->isMonthlyBudget()) {
            if ($this->statistic === null) {
                return 0.00;
            }

            return $this->statistic->getRate();
        }

        if ($this->statisticTotal === null) {
            return 0.00;
        }

        return $this->statisticTotal->getRate();
    }

    public function getDuration(): int
    {
        if ($this->isMonthlyBudget()) {
            if ($this->statistic === null) {
                return 0;
            }

            return $this->statistic->getDuration();
        }

        if ($this->statisticTotal === null) {
            return 0;
        }

        return $this->statisticTotal->getDuration();
    }

    public function getInternalRate(): float
    {
        if ($this->isMonthlyBudget()) {
            if ($this->statistic === null) {
                return 0.00;
            }

            return $this->statistic->getInternalRate();
        }

        if ($this->statisticTotal === null) {
            return 0.00;
        }

        return $this->statisticTotal->getInternalRate();
    }
}
