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

    public function setTrackingMode(string $trackingMode): TimesheetConfig
    {
        $this->trackingMode = $trackingMode;

        return $this;
    }

    public function getDefaultBeginTime(): string
    {
        return $this->defaultBeginTime;
    }

    public function setDefaultBeginTime(string $defaultBeginTime): TimesheetConfig
    {
        $this->defaultBeginTime = $defaultBeginTime;

        return $this;
    }

    public function getActiveEntriesHardLimit(): int
    {
        return $this->activeEntriesHardLimit;
    }

    public function setActiveEntriesHardLimit(int $activeEntriesHardLimit): TimesheetConfig
    {
        $this->activeEntriesHardLimit = $activeEntriesHardLimit;

        return $this;
    }

    public function getActiveEntriesSoftLimit(): int
    {
        return $this->activeEntriesSoftLimit;
    }

    public function setActiveEntriesSoftLimit(int $activeEntriesSoftLimit): TimesheetConfig
    {
        $this->activeEntriesSoftLimit = $activeEntriesSoftLimit;

        return $this;
    }

    public function isAllowFutureTimes(): bool
    {
        return $this->isAllowFutureTimes;
    }

    public function setIsAllowFutureTimes(bool $isAllowFutureTimes): TimesheetConfig
    {
        $this->isAllowFutureTimes = $isAllowFutureTimes;

        return $this;
    }
}
