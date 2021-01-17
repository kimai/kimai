<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

/**
 * @deprecated since 1.11 - use SystemConfiguration instead
 */
class CalendarConfiguration implements SystemBundleConfiguration
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
        return 'calendar';
    }

    public function getBusinessDays(): array
    {
        return $this->configuration->getCalendarBusinessDays();
    }

    public function getBusinessTimeBegin(): string
    {
        return $this->configuration->getCalendarBusinessTimeBegin();
    }

    public function getBusinessTimeEnd(): string
    {
        return $this->configuration->getCalendarBusinessTimeEnd();
    }

    public function getTimeframeBegin(): string
    {
        return $this->configuration->getCalendarTimeframeBegin();
    }

    public function getTimeframeEnd(): string
    {
        return $this->configuration->getCalendarTimeframeEnd();
    }

    public function getDayLimit(): int
    {
        return $this->configuration->getCalendarDayLimit();
    }

    public function isShowWeekNumbers(): bool
    {
        return $this->configuration->isCalendarShowWeekNumbers();
    }

    public function isShowWeekends(): bool
    {
        return $this->configuration->isCalendarShowWeekends();
    }

    public function getGoogleApiKey(): ?string
    {
        return $this->configuration->getCalendarGoogleApiKey();
    }

    public function getGoogleSources(): ?array
    {
        return $this->configuration->getCalendarGoogleSources();
    }

    public function getSlotDuration(): string
    {
        return $this->configuration->getCalendarSlotDuration();
    }
}
