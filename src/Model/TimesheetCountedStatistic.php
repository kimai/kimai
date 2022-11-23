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

    private $recordRateBillableExported = 0.0;
    private $recordDurationBillableExported = 0;

    private $counterExported = 0;
    private $recordDurationExported = 0;
    private $recordRateExported = 0.0;
    private $internalRateExported = 0.0;

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

    public function getCounterExported(): int
    {
        return $this->counterExported;
    }

    public function setCounterExported(int $counter): void
    {
        $this->counterExported = $counter;
    }

    /**
     * Returns the total amount of included timesheet records.
     *
     * @return int
     * @deprecated since 1.15 use getCounter() instead
     */
    public function getRecordAmount()
    {
        return $this->getCounter();
    }

    /**
     * @param int $recordAmount
     * @return $this
     */
    public function setRecordAmount($recordAmount)
    {
        $this->setCounter((int) $recordAmount);

        return $this;
    }

    /**
     * For unified access, used in frontend.
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->getDuration();
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
    public function getRecordDuration(): int
    {
        return $this->getDuration();
    }

    /**
     * @param int $recordDuration
     * @return $this
     */
    public function setRecordDuration($recordDuration)
    {
        $this->setDuration((int) $recordDuration);

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
    public function getRecordRate(): float
    {
        return $this->getRate();
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
        $this->setRate((float) $recordRate);

        return $this;
    }

    /**
     * @deprecated since 1.15 use getInternalRate() instead
     */
    public function getRecordInternalRate(): float
    {
        return $this->getInternalRate();
    }

    /**
     * Returns the total internal rate of all included timesheet records.
     *
     * @return float
     */
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

    public function getInternalRateExported(): float
    {
        return $this->internalRateExported;
    }

    public function setInternalRateExported(float $internalRateExported): void
    {
        $this->internalRateExported = $internalRateExported;
    }

    /**
     * @param float $recordInternalRate
     * @return $this
     * @deprecated since 1.15 use setInternalRate() instead
     */
    public function setRecordInternalRate($recordInternalRate)
    {
        $this->setInternalRate((float) $recordInternalRate);

        return $this;
    }

    public function setInternalRate(float $internalRate): void
    {
        $this->recordInternalRate = $internalRate;
    }

    /**
     * @deprecated since 1.15 use getCounterBillable() instead
     */
    public function getRecordAmountBillable(): int
    {
        return $this->getCounterBillable();
    }

    public function setRecordAmountBillable(int $recordAmount): void
    {
        $this->setCounterBillable($recordAmount);
    }

    public function getDurationBillable(): int
    {
        return $this->recordDurationBillable;
    }

    public function setDurationBillable(int $recordDuration): void
    {
        $this->recordDurationBillable = $recordDuration;
    }

    public function getDurationBillableExported(): int
    {
        return $this->recordDurationBillableExported;
    }

    public function setDurationBillableExported(int $recordDuration): void
    {
        $this->recordDurationBillableExported = $recordDuration;
    }

    public function getRateBillable(): float
    {
        return $this->recordRateBillable;
    }

    public function setRateBillable(float $recordRate): void
    {
        $this->recordRateBillable = $recordRate;
    }

    public function getRateBillableExported(): float
    {
        return $this->recordRateBillableExported;
    }

    public function setRateBillableExported(float $recordRate): void
    {
        $this->recordRateBillableExported = $recordRate;
    }

    public function getDurationExported(): int
    {
        return $this->recordDurationExported;
    }

    public function setDurationExported(int $recordDuration): void
    {
        $this->recordDurationExported = $recordDuration;
    }

    public function getRateExported(): float
    {
        return $this->recordRateExported;
    }

    public function setRateExported(float $recordRate): void
    {
        $this->recordRateExported = $recordRate;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'duration' => $this->recordDuration,
            'duration_billable' => $this->recordDurationBillable,
            'duration_exported' => $this->recordDurationExported,
            'duration_billable_exported' => $this->recordDurationBillableExported,
            'rate' => $this->recordRate,
            'rate_billable' => $this->recordRateBillable,
            'rate_exported' => $this->recordRateExported,
            'rate_billable_exported' => $this->recordRateBillableExported,
            'rate_internal' => $this->recordInternalRate,
            'rate_internal_exported' => $this->internalRateExported,
            'amount' => $this->counter,
            'amount_billable' => $this->counterBillable,
            'amount_exported' => $this->counterExported,
        ];
    }
}
