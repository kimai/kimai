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
use DateTime;

final class ProjectViewModel
{
    private int $timesheetCounter = 0;
    private int $durationDay = 0;
    private int $durationWeek = 0;
    private int $durationMonth = 0;
    private int $durationTotal = 0;
    private float $rateTotal = 0.00;
    private int $notExportedDuration = 0;
    private float $notExportedRate = 0.00;
    private int $notBilledDuration = 0;
    private float $notBilledRate = 0.00;
    private int $billableDuration = 0;
    private float $billableRate = 0.00;
    private ?DateTime $lastRecord = null;
    private ?BudgetStatisticModelInterface $budgetStatisticModel = null;

    public function __construct(private Project $project)
    {
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getTimesheetCounter(): int
    {
        return $this->timesheetCounter;
    }

    public function setTimesheetCounter(int $timesheetCounter): void
    {
        $this->timesheetCounter = $timesheetCounter;
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

    public function getDurationTotal(): int
    {
        return $this->durationTotal;
    }

    public function setDurationTotal(int $durationTotal): void
    {
        $this->durationTotal = $durationTotal;
    }

    public function getNotExportedDuration(): int
    {
        return $this->notExportedDuration;
    }

    public function setNotExportedDuration(int $notExportedDuration): void
    {
        $this->notExportedDuration = $notExportedDuration;
    }

    public function getNotExportedRate(): float
    {
        return $this->notExportedRate;
    }

    public function setNotExportedRate(float $notExportedRate): void
    {
        $this->notExportedRate = $notExportedRate;
    }

    public function getNotBilledDuration(): int
    {
        return $this->notBilledDuration;
    }

    public function setNotBilledDuration(int $notBilledDuration): void
    {
        $this->notBilledDuration = $notBilledDuration;
    }

    public function getNotBilledRate(): float
    {
        return $this->notBilledRate;
    }

    public function setNotBilledRate(float $notBilledRate): void
    {
        $this->notBilledRate = $notBilledRate;
    }

    public function getBillableDuration(): int
    {
        return $this->billableDuration;
    }

    public function setBillableDuration(int $billableDuration): void
    {
        $this->billableDuration = $billableDuration;
    }

    public function getBillableRate(): float
    {
        return $this->billableRate;
    }

    public function setBillableRate(float $billableRate): void
    {
        $this->billableRate = $billableRate;
    }

    public function getRateTotal(): float
    {
        return $this->rateTotal;
    }

    public function setRateTotal(float $rateTotal): void
    {
        $this->rateTotal = $rateTotal;
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

    public function setBudgetStatisticModel(BudgetStatisticModelInterface $budgetStatisticModel): void
    {
        $this->budgetStatisticModel = $budgetStatisticModel;
    }
}
