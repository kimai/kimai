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
    use StringAccessibleConfigTrait;

    public function getPrefix(): string
    {
        return 'calendar';
    }

    /**
     * @return array
     */
    public function getBusinessDays(): array
    {
        return (array) $this->find('businessHours.days');
    }

    /**
     * @return string
     */
    public function getBusinessTimeBegin(): string
    {
        return (string) $this->find('businessHours.begin');
    }

    /**
     * @return string
     */
    public function getBusinessTimeEnd(): string
    {
        return (string) $this->find('businessHours.end');
    }

    /**
     * @return string
     */
    public function getTimeframeBegin(): string
    {
        return (string) $this->find('visibleHours.begin');
    }

    /**
     * @return string
     */
    public function getTimeframeEnd(): string
    {
        return (string) $this->find('visibleHours.end');
    }

    /**
     * @return int
     */
    public function getDayLimit(): int
    {
        return (int) $this->find('day_limit');
    }

    /**
     * @return bool
     */
    public function isShowWeekNumbers(): bool
    {
        return (bool) $this->find('week_numbers');
    }

    /**
     * @return bool
     */
    public function isShowWeekends(): bool
    {
        return (bool) $this->find('weekends');
    }

    /**
     * @return null|string
     */
    public function getGoogleApiKey(): ?string
    {
        return $this->find('google.api_key');
    }

    /**
     * @return null|array
     */
    public function getGoogleSources(): ?array
    {
        return $this->find('google.sources');
    }

    public function getSlotDuration(): string
    {
        return (string) $this->find('slot_duration');
    }
}
