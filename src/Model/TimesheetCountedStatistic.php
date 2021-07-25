<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class TimesheetCountedStatistic implements \JsonSerializable
{
    private $counter = 0;
    private $recordDuration = 0;
    private $recordRate = 0.0;
    private $recordInternalRate = 0.0;

    private $counterBillable = 0;
    private $recordDurationBillable = 0;
    private $recordRateBillable = 0.0;
    private $internalRateBillable = 0.0;

    /**
     * For unified access, used in frontend.
     *
     * @return int
     */
    public function getCounter(): int
    {
        return $this->counter;
    }

    public function setCounter(int $counter): void
    {
        $this->counter = $counter;
    }

    public function getCounterBillable(): int
    {
        return $this->counterBillable;
    }

    public function setCounterBillable(int $counter): void
    {
        $this->counterBillable = $counter;
    }

    /**
     * Returns the total amount of included timesheet records.
     *
     * @return int
     */
    public function getRecordAmount()
    {
        return $this->counter;
    }

    /**
     * @param int $recordAmount
     * @return $this
     */
    public function setRecordAmount($recordAmount)
    {
        $this->counter = (int) $recordAmount;

        return $this;
    }

    /**
     * For unified access, used in frontend.
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->recordDuration;
    }

    public function setDuration(int $duration): void
    {
        $this->recordDuration = $duration;
    }

    /**
     * For unified access, used in frontend.
     *
     * @return int
     */
    public function getDuration(): int
    {
        return $this->recordDuration;
    }

    /**
     * Returns the total duration of all included timesheet records.
     *
     * @return int
     */
    public function getRecordDuration()
    {
        return $this->recordDuration;
    }

    /**
     * @param int $recordDuration
     * @return $this
     */
    public function setRecordDuration($recordDuration)
    {
        $this->recordDuration = (int) $recordDuration;

        return $this;
    }

    /**
     * For unified access, used in frontend.
     *
     * @return float
     */
    public function getRate(): float
    {
        return $this->recordRate;
    }

    /**
     * Returns the total rate of all included timesheet records.
     *
     * @return float
     */
    public function getRecordRate()
    {
        return $this->recordRate;
    }

    public function setRate(float $rate): void
    {
        $this->recordRate = $rate;
    }

    /**
     * @param float $recordRate
     * @return $this
     */
    public function setRecordRate($recordRate)
    {
        $this->recordRate = (float) $recordRate;

        return $this;
    }

    /**
     * Returns the total internal rate of all included timesheet records.
     *
     * @return float
     */
    public function getRecordInternalRate()
    {
        return $this->recordInternalRate;
    }

    public function getInternalRate(): float
    {
        return $this->recordInternalRate;
    }

    public function getInternalRateBillable(): float
    {
        return $this->internalRateBillable;
    }

    public function setInternalRateBillable(float $internalRateBillable): void
    {
        $this->internalRateBillable = $internalRateBillable;
    }

    /**
     * @param float $recordInternalRate
     * @return $this
     */
    public function setRecordInternalRate($recordInternalRate)
    {
        $this->recordInternalRate = (float) $recordInternalRate;

        return $this;
    }

    public function setInternalRate(float $internalRate): void
    {
        $this->recordInternalRate = $internalRate;
    }

    public function getRecordAmountBillable(): int
    {
        return $this->counterBillable;
    }

    public function setRecordAmountBillable(int $recordAmount): void
    {
        $this->counterBillable = $recordAmount;
    }

    public function getDurationBillable(): int
    {
        return $this->recordDurationBillable;
    }

    public function setDurationBillable(int $recordDuration): void
    {
        $this->recordDurationBillable = $recordDuration;
    }

    public function getRateBillable(): float
    {
        return $this->recordRateBillable;
    }

    public function setRateBillable(float $recordRate): void
    {
        $this->recordRateBillable = $recordRate;
    }

    public function jsonSerialize()
    {
        return [
            'duration' => $this->recordDuration,
            'duration_billable' => $this->recordDurationBillable,
            'rate' => $this->recordRate,
            'rate_billable' => $this->recordRateBillable,
            'rate_internal' => $this->recordInternalRate,
            'amount' => $this->counter,
            'amount_billable' => $this->counterBillable,
        ];
    }
}
