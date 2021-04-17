<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectView;

use App\Entity\Project;

final class ProjectViewModel
{
    private $project;
    private $durationDay = 0;
    private $durationWeek = 0;
    private $durationMonth = 0;
    private $durationTotal = 0;
    private $rateTotal = 0.00;
    private $notExportedDuration = 0;
    private $notExportedRate = 0.00;
    private $notBilledDuration = 0;
    private $notBilledRate = 0.00;
    private $noneBillableDuration = 0;
    private $noneBillableRate = 0.00;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getProject(): Project
    {
        return $this->project;
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

    public function getNoneBillableDuration(): int
    {
        return $this->noneBillableDuration;
    }

    public function setNoneBillableDuration(int $noneBillableDuration): void
    {
        $this->noneBillableDuration = $noneBillableDuration;
    }

    public function getNoneBillableRate(): float
    {
        return $this->noneBillableRate;
    }

    public function setNoneBillableRate(float $noneBillableRate): void
    {
        $this->noneBillableRate = $noneBillableRate;
    }

    public function getBillableDuration(): int
    {
        return $this->durationTotal - $this->noneBillableDuration;
    }

    public function getBillableRate(): float
    {
        return $this->rateTotal - $this->noneBillableRate;
    }

    public function getRateTotal(): float
    {
        return $this->rateTotal;
    }

    public function setRateTotal(float $rateTotal): void
    {
        $this->rateTotal = $rateTotal;
    }
}
