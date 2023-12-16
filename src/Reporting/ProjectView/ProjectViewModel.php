<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectView;

use App\Entity\Project;
use App\Model\BudgetStatisticModelInterface;
use App\Model\ProjectBudgetStatisticModel;
use App\Model\Statistic\BudgetStatistic;
use DateTime;

final class ProjectViewModel
{
    private int $durationDay = 0;
    private int $durationWeek = 0;
    private int $durationMonth = 0;
    private ?DateTime $lastRecord = null;

    public function __construct(private ProjectBudgetStatisticModel $budgetStatisticModel)
    {
    }

    public function getProject(): Project
    {
        return $this->budgetStatisticModel->getProject();
    }

    public function getDurationDay(): int
    {
        return $this->durationDay;
    }

    public function setDurationDay(int $durationDay): void
    {
        $this->durationDay = $durationDay;
    }

    public function getDurationWeek(): int
    {
        return $this->durationWeek;
    }

    public function setDurationWeek(int $durationWeek): void
    {
        $this->durationWeek = $durationWeek;
    }

    public function getDurationMonth(): int
    {
        return $this->durationMonth;
    }

    public function setDurationMonth(int $durationMonth): void
    {
        $this->durationMonth = $durationMonth;
    }

    private function getTotals(): BudgetStatistic
    {
        if ($this->budgetStatisticModel->getStatisticTotal() === null) {
            throw new \InvalidArgumentException('Totals must not be null');
        }

        return $this->budgetStatisticModel->getStatisticTotal();
    }

    public function getDurationTotal(): int
    {
        return $this->getTotals()->getDuration();
    }

    public function getNotExportedDuration(): int
    {
        $totals = $this->getTotals();

        return $totals->getDurationBillable() - $totals->getDurationBillableExported();
    }

    public function getNotExportedRate(): float
    {
        $totals = $this->getTotals();

        return $totals->getRateBillable() - $totals->getRateBillableExported();
    }

    public function getBillableDuration(): int
    {
        return $this->getTotals()->getDurationBillable();
    }

    public function getBillableRate(): float
    {
        return $this->getTotals()->getRateBillable();
    }

    public function getRateTotal(): float
    {
        return $this->getTotals()->getRate();
    }

    public function getLastRecord(): ?DateTime
    {
        return $this->lastRecord;
    }

    public function setLastRecord(DateTime $lastRecord): void
    {
        $this->lastRecord = $lastRecord;
    }

    public function getBudgetStatisticModel(): BudgetStatisticModelInterface
    {
        return $this->budgetStatisticModel;
    }
}
