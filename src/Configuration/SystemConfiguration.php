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

    // ========== Calendar configurations ==========

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

    // ========== Customer configurations ==========

    public function getCustomerDefaultTimezone(): ?string
    {
        return $this->find('defaults.customer.timezone');
    }

    public function getCustomerDefaultCurrency(): string
    {
        return $this->find('defaults.customer.currency');
    }

    public function getCustomerDefaultCountry(): string
    {
        return $this->find('defaults.customer.country');
    }

    // ========== User configurations ==========

    public function getUserDefaultTimezone(): ?string
    {
        return $this->find('defaults.user.timezone');
    }

    public function getUserDefaultTheme(): ?string
    {
        return $this->find('defaults.user.theme');
    }

    public function getUserDefaultLanguage(): string
    {
        return $this->find('defaults.user.language');
    }

    public function getUserDefaultCurrency(): string
    {
        return $this->find('defaults.user.currency');
    }

    // ========== Timesheet configurations ==========

    public function getTimesheetDefaultBeginTime(): string
    {
        return (string) $this->find('timesheet.default_begin');
    }

    public function isTimesheetAllowFutureTimes(): bool
    {
        return (bool) $this->find('timesheet.rules.allow_future_times');
    }

    public function isTimesheetAllowOverlappingRecords(): bool
    {
        return (bool) $this->find('timesheet.rules.allow_overlapping_records');
    }

    public function getTimesheetTrackingMode(): string
    {
        return (string) $this->find('timesheet.mode');
    }

    public function isTimesheetMarkdownEnabled(): bool
    {
        return (bool) $this->find('timesheet.markdown_content');
    }

    public function getTimesheetActiveEntriesHardLimit(): int
    {
        return (int) $this->find('timesheet.active_entries.hard_limit');
    }

    public function getTimesheetActiveEntriesSoftLimit(): int
    {
        return (int) $this->find('timesheet.active_entries.soft_limit');
    }

    public function getTimesheetDefaultRoundingDays(): string
    {
        return (string) $this->find('timesheet.rounding.default.days');
    }

    public function getTimesheetDefaultRoundingMode(): string
    {
        return (string) $this->find('timesheet.rounding.default.mode');
    }

    public function getTimesheetDefaultRoundingBegin(): int
    {
        return (int) $this->find('timesheet.rounding.default.begin');
    }

    public function getTimesheetDefaultRoundingEnd(): int
    {
        return (int) $this->find('timesheet.rounding.default.end');
    }

    public function getTimesheetDefaultRoundingDuration(): int
    {
        return (int) $this->find('timesheet.rounding.default.duration');
    }

    public function getTimesheetLockdownPeriodStart(): string
    {
        return (string) $this->find('timesheet.rules.lockdown_period_start');
    }

    public function getTimesheetLockdownPeriodEnd(): string
    {
        return (string) $this->find('timesheet.rules.lockdown_period_end');
    }

    public function getTimesheetLockdownGracePeriod(): string
    {
        return (string) $this->find('timesheet.rules.lockdown_grace_period');
    }

    public function isTimesheetLockdownActive(): bool
    {
        return !empty($this->find('timesheet.rules.lockdown_period_start')) && !empty($this->find('timesheet.rules.lockdown_period_end'));
    }

    private function getIncrement(string $key, int $fallback, int $min = 1): ?int
    {
        $config = $this->find($key);

        if ($config === null || trim($config) === '') {
            return $fallback;
        }

        $config = (int) $config;

        return $config < $min ? null : $config;
    }

    public function getTimesheetIncrementDuration(): ?int
    {
        return $this->getIncrement('timesheet.duration_increment', $this->getTimesheetDefaultRoundingDuration(), 1);
    }

    public function getTimesheetIncrementBegin(): ?int
    {
        return $this->getIncrement('timesheet.time_increment', $this->getTimesheetDefaultRoundingBegin(), 0);
    }

    public function getTimesheetIncrementEnd(): ?int
    {
        return $this->getIncrement('timesheet.time_increment', $this->getTimesheetDefaultRoundingEnd(), 0);
    }
}
