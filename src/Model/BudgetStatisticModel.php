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
    /**
     * @var EntityWithBudget
     */
    private $entity;
    /**
     * @var BudgetStatistic
     */
    private $statistic;
    /**
     * @var BudgetStatistic
     */
    private $statisticTotal;

    public function __construct(EntityWithBudget $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): EntityWithBudget
    {
        return $this->entity;
    }

    public function getStatistic(): BudgetStatistic
    {
        return $this->statistic;
    }

    public function setStatistic(BudgetStatistic $statistic)
    {
        $this->statistic = $statistic;
    }

    public function getStatisticTotal(): BudgetStatistic
    {
        return $this->statisticTotal;
    }

    public function setStatisticTotal(BudgetStatistic $statistic)
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
            return $this->statistic->getDurationBillable();
        }

        return $this->statisticTotal->getDurationBillable();
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

    public function getBudgetSpent(): float
    {
        return $this->getRateBillable();
    }

    public function getRateBillable(): float
    {
        if ($this->isMonthlyBudget()) {
            return $this->statistic->getRateBillable();
        }

        return $this->statisticTotal->getRateBillable();
    }

    public function getRate(): float
    {
        if ($this->isMonthlyBudget()) {
            return $this->statistic->getRate();
        }

        return $this->statisticTotal->getRate();
    }

    public function getDuration(): int
    {
        if ($this->isMonthlyBudget()) {
            return $this->statistic->getDuration();
        }

        return $this->statisticTotal->getDuration();
    }

    public function getInternalRate(): float
    {
        if ($this->isMonthlyBudget()) {
            return $this->statistic->getInternalRate();
        }

        return $this->statisticTotal->getInternalRate();
    }
}
