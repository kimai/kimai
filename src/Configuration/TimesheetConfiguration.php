<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

/**
 * @deprecated since 1.13, use SystemConfiguration instead
 */
class TimesheetConfiguration implements SystemBundleConfiguration
{
    private $configuration;

    public function __construct(SystemConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function find(string $key)
    {
        if (strpos($key, $this->getPrefix() . '.') === false) {
            $key = $this->getPrefix() . '.' . $key;
        }

        return $this->configuration->find($key);
    }

    public function getPrefix(): string
    {
        return 'timesheet';
    }

    public function isAllowFutureTimes(): bool
    {
        return $this->configuration->isTimesheetAllowFutureTimes();
    }

    public function isAllowOverlappingRecords(): bool
    {
        return $this->configuration->isTimesheetAllowOverlappingRecords();
    }

    public function getTrackingMode(): string
    {
        return $this->configuration->getTimesheetTrackingMode();
    }

    public function getDefaultBeginTime(): string
    {
        return $this->configuration->getTimesheetDefaultBeginTime();
    }

    public function isMarkdownEnabled(): bool
    {
        return $this->configuration->isTimesheetMarkdownEnabled();
    }

    public function getActiveEntriesHardLimit(): int
    {
        return $this->configuration->getTimesheetActiveEntriesHardLimit();
    }

    public function getActiveEntriesSoftLimit(): int
    {
        return $this->configuration->getTimesheetActiveEntriesSoftLimit();
    }

    public function getDefaultRoundingDays(): string
    {
        return $this->configuration->getTimesheetDefaultRoundingDays();
    }

    public function getDefaultRoundingMode(): string
    {
        return $this->configuration->getTimesheetDefaultRoundingMode();
    }

    public function getDefaultRoundingBegin(): int
    {
        return $this->configuration->getTimesheetDefaultRoundingBegin();
    }

    public function getDefaultRoundingEnd(): int
    {
        return $this->configuration->getTimesheetDefaultRoundingEnd();
    }

    public function getDefaultRoundingDuration(): int
    {
        return $this->configuration->getTimesheetDefaultRoundingDuration();
    }

    public function getLockdownPeriodStart(): string
    {
        return $this->configuration->getTimesheetLockdownPeriodStart();
    }

    public function getLockdownPeriodEnd(): string
    {
        return $this->configuration->getTimesheetLockdownPeriodEnd();
    }

    public function getLockdownGracePeriod(): string
    {
        return $this->configuration->getTimesheetLockdownGracePeriod();
    }

    public function isLockdownActive(): bool
    {
        return $this->configuration->isTimesheetLockdownActive();
    }
}
