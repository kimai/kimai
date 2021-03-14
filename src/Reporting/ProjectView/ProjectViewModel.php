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
    /**
     * @var Project
     */
    private $project;
    /**
     * @var int
     */
    private $durationDay = 0;
    /**
     * @var int
     */
    private $durationWeek = 0;
    /**
     * @var int
     */
    private $durationMonth = 0;
    /**
     * @var int
     */
    private $durationTotal = 0;
    /**
     * @var float
     */
    private $rateTotal = 0.00;
    /**
     * @var int
     */
    private $notExportedDuration = 0;
    /**
     * @var float
     */
    private $notExportedRate = 0.00;

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
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

    public function getRateTotal(): float
    {
        return $this->rateTotal;
    }

    public function setRateTotal(float $rateTotal): void
    {
        $this->rateTotal = $rateTotal;
    }
}
