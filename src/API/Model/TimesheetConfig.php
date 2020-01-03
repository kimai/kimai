<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

final class TimesheetConfig
{
    /**
     * See here: https://www.kimai.org/documentation/timesheet.html#tracking-modes
     *
     * @var string
     */
    private $trackingMode = 'default';
    /**
     * @var string
     */
    private $defaultBeginTime = 'now';
    /**
     * @var int
     */
    private $activeEntriesHardLimit = 1;
    /**
     * @var int
     */
    private $activeEntriesSoftLimit = 1;
    /**
     * @var bool
     */
    private $isAllowFutureTimes = true;

    /**
     * @return string
     */
    public function getTrackingMode(): string
    {
        return $this->trackingMode;
    }

    /**
     * @param string $trackingMode
     * @return TimesheetConfig
     */
    public function setTrackingMode(string $trackingMode): TimesheetConfig
    {
        $this->trackingMode = $trackingMode;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultBeginTime(): string
    {
        return $this->defaultBeginTime;
    }

    /**
     * @param string $defaultBeginTime
     * @return TimesheetConfig
     */
    public function setDefaultBeginTime(string $defaultBeginTime): TimesheetConfig
    {
        $this->defaultBeginTime = $defaultBeginTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getActiveEntriesHardLimit(): int
    {
        return $this->activeEntriesHardLimit;
    }

    /**
     * @param int $activeEntriesHardLimit
     * @return TimesheetConfig
     */
    public function setActiveEntriesHardLimit(int $activeEntriesHardLimit): TimesheetConfig
    {
        $this->activeEntriesHardLimit = $activeEntriesHardLimit;

        return $this;
    }

    /**
     * @return int
     */
    public function getActiveEntriesSoftLimit(): int
    {
        return $this->activeEntriesSoftLimit;
    }

    /**
     * @param int $activeEntriesSoftLimit
     * @return TimesheetConfig
     */
    public function setActiveEntriesSoftLimit(int $activeEntriesSoftLimit): TimesheetConfig
    {
        $this->activeEntriesSoftLimit = $activeEntriesSoftLimit;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowFutureTimes(): bool
    {
        return $this->isAllowFutureTimes;
    }

    /**
     * @param bool $isAllowFutureTimes
     * @return TimesheetConfig
     */
    public function setIsAllowFutureTimes(bool $isAllowFutureTimes): TimesheetConfig
    {
        $this->isAllowFutureTimes = $isAllowFutureTimes;

        return $this;
    }
}
