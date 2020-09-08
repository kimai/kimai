<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

class SystemConfiguration implements SystemBundleConfiguration
{
    use StringAccessibleConfigTrait;

    public function getPrefix(): string
    {
        return 'kimai';
    }

    protected function getConfigurations(ConfigLoaderInterface $repository): array
    {
        return $repository->getConfiguration();
    }

    /**
     * @return array
     */
    public function getCalendarBusinessDays(): array
    {
        return (array) $this->find('calendar.businessHours.days');
    }

    public function getCalendarBusinessTimeBegin(): string
    {
        return (string) $this->find('calendar.businessHours.begin');
    }

    public function getCalendarBusinessTimeEnd(): string
    {
        return (string) $this->find('calendar.businessHours.end');
    }

    public function getCalendarTimeframeBegin(): string
    {
        return (string) $this->find('calendar.visibleHours.begin');
    }

    public function getCalendarTimeframeEnd(): string
    {
        return (string) $this->find('calendar.visibleHours.end');
    }

    public function getCalendarDayLimit(): int
    {
        return (int) $this->find('calendar.day_limit');
    }

    public function isCalendarShowWeekNumbers(): bool
    {
        return (bool) $this->find('calendar.week_numbers');
    }

    public function isCalendarShowWeekends(): bool
    {
        return (bool) $this->find('calendar.weekends');
    }

    public function getCalendarGoogleApiKey(): ?string
    {
        return $this->find('calendar.google.api_key');
    }

    public function getCalendarGoogleSources(): ?array
    {
        return $this->find('calendar.google.sources');
    }

    public function getCalendarSlotDuration(): string
    {
        return (string) $this->find('calendar.slot_duration');
    }
}
