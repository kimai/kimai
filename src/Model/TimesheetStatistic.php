<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

/**
 * Timesheet statistics for one user.
 */
class TimesheetStatistic
{
    /**
     * @var \DateTime|null
     */
    protected ?\DateTime $firstEntry = null;
    protected int $durationThisMonth = 0;
    protected int $durationTotal = 0;
    protected float $amountThisMonth = 0.0;
    protected float $amountTotal = 0.0;
    protected float $amountThisMonthBillable = 0.0;
    protected float $amountTotalBillable = 0.0;
    protected int $recordsTotal = 0;

    public function getDurationThisMonth(): int
    {
        return $this->durationThisMonth;
    }

    public function setDurationThisMonth(int $durationThisMonth): void
    {
        $this->durationThisMonth = $durationThisMonth;
    }

    /**
     * This is actually the rate, wrong wording...
     *
     * @return float
     */
    public function getAmountTotal(): float
    {
        return $this->amountTotal;
    }

    /**
     * This is actually the rate, wrong wording...
     *
     * @param float|int $amountTotal
     */
    public function setAmountTotal($amountTotal): void
    {
        $this->amountTotal = (float) $amountTotal;
    }

    public function getRateTotalBillable(): float
    {
        return $this->amountTotalBillable;
    }

    public function setRateTotalBillable(float $amountTotal): void
    {
        $this->amountTotalBillable = $amountTotal;
    }

    public function getDurationTotal(): int
    {
        return $this->durationTotal;
    }

    /**
     * @param int $durationTotal
     */
    public function setDurationTotal($durationTotal): void
    {
        $this->durationTotal = (int) $durationTotal;
    }

    /**
     * This is actually the rate, wrong wording...
     *
     * @return float
     */
    public function getAmountThisMonth(): float
    {
        return $this->amountThisMonth;
    }

    /**
     * This is actually the rate, wrong wording...
     *
     * @param float|int $amountThisMonth
     */
    public function setAmountThisMonth($amountThisMonth): void
    {
        $this->amountThisMonth = (float) $amountThisMonth;
    }

    public function getRateThisMonthBillable(): float
    {
        return $this->amountThisMonthBillable;
    }

    public function setRateThisMonthBillable(float $amountThisMonth): void
    {
        $this->amountThisMonthBillable = $amountThisMonth;
    }

    public function getRecordsTotal(): int
    {
        return $this->recordsTotal;
    }

    public function setRecordsTotal(int $recordsTotal): void
    {
        $this->recordsTotal = $recordsTotal;
    }
}
