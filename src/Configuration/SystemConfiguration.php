<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Constants;

final class SystemConfiguration
{
    private bool $initialized = false;

    public function __construct(private ConfigLoaderInterface $repository, private array $settings = [])
    {
    }

    private function prepare(): void
    {
        if ($this->initialized) {
            return;
        }

        foreach ($this->repository->getConfigurations() as $key => $value) {
            $this->set($key, $value);
        }

        $this->initialized = true;
    }

    /**
     * Set a new or replace an existing system configuration.
     */
    public function set(string $key, mixed $value): void
    {
        if (\array_key_exists($key, $this->settings)) {
            if (\is_bool($this->settings[$key])) {
                $value = (bool) $value;
            } elseif (\is_int($this->settings[$key])) {
                $value = (int) $value;
            }
        }
        $this->settings[$key] = $value;
    }

    /**
     * @param string $key
     * @return string|int|bool|float|null
     */
    public function find(string $key): string|int|bool|float|null
    {
        $this->prepare();

        if (\array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        return null;
    }

    /**
     * This method should be avoided if possible, use plain keys instead.
     *
     * @see https://github.com/divineomega/array_undot
     * @param string $key
     * @return array
     */
    public function findArray(string $key): array
    {
        $this->prepare();

        $result = array_filter($this->settings, function ($settingName) use ($key): bool {
            return str_starts_with($settingName, $key);
        }, ARRAY_FILTER_USE_KEY);

        $replaced = [];
        foreach ($result as $settingName => $value) {
            if (\is_bool($this->settings[$settingName])) {
                $value = (bool) $value;
            } elseif (\is_int($this->settings[$settingName])) {
                $value = (int) $value;
            }

            $baseName = str_replace($key . '.', '', $settingName);

            $keys = explode('.', $baseName);
            $array = &$replaced;
            while (\count($keys) > 1) {
                $search = array_shift($keys);
                /* @phpstan-ignore-next-line  */
                if (!\array_key_exists($search, $array) || !\is_array($array[$search])) {
                    $array[$search] = [];
                }

                $array = &$array[$search];
            }
            $array[array_shift($keys)] = $value;
        }

        return $replaced;
    }

    public function has(string $key): bool
    {
        $this->prepare();

        if (\array_key_exists($key, $this->settings)) {
            return true;
        }

        $result = array_filter($this->settings, function ($settingName) use ($key): bool {
            return str_starts_with($settingName, $key);
        }, ARRAY_FILTER_USE_KEY);

        return \count($result) > 0;
    }

    // ========== Array access methods ==========

    /**
     * @deprecated since 2.0.35
     */
    public function offsetExists($offset): bool
    {
        @trigger_error('The method "SystemConfiguration::offsetExists()" is deprecated, use "has()" instead', E_USER_DEPRECATED);

        return $this->has($offset);
    }

    /**
     * @deprecated since 2.0.35
     */
    public function offsetGet($offset): mixed
    {
        @trigger_error('The method "SystemConfiguration::offsetGet()" is deprecated, use "find()" instead', E_USER_DEPRECATED);

        return $this->find($offset);
    }

    /**
     * @deprecated since 2.0.35
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        @trigger_error('The method "SystemConfiguration::offsetSet()" is deprecated, use "set()" instead', E_USER_DEPRECATED);

        $this->set($offset, $value);
    }

    // ========== Authentication configurations ==========

    public function isLoginFormActive(): bool
    {
        if ($this->isLdapActive()) {
            return true;
        }

        // if SAML is active, the login form can be deactivated
        if (!$this->isSamlActive()) {
            return true;
        }

        return (bool) $this->find('user.login');
    }

    public function isSelfRegistrationActive(): bool
    {
        if (!$this->isLoginFormActive()) {
            return false;
        }

        return (bool) $this->find('user.registration');
    }

    public function getPasswordResetTokenLifetime(): int
    {
        return (int) $this->find('user.password_reset_token_ttl');
    }

    public function getPasswordResetRetryLifetime(): int
    {
        return (int) $this->find('user.password_reset_retry_ttl');
    }

    public function isPasswordResetActive(): bool
    {
        if (!$this->isLoginFormActive()) {
            return false;
        }

        return (bool) $this->find('user.password_reset');
    }

    public function isSamlActive(): bool
    {
        return (bool) $this->find('saml.activate');
    }

    public function getSamlTitle(): string
    {
        return (string) $this->find('saml.title');
    }

    public function getSamlProvider(): ?string
    {
        return $this->find('saml.provider');
    }

    public function isSamlRolesResetOnLogin(): bool
    {
        return (bool) $this->find('saml.roles.resetOnLogin');
    }

    /**
     * @return array<int, array<'saml'|'kimai', string>>
     */
    public function getSamlRolesMapping(): array
    {
        return $this->findArray('saml.roles.mapping');
    }

    /**
     * @return array<string, array<mixed>|bool>
     */
    public function getSamlConnection(): array
    {
        return $this->findArray('saml.connection');
    }

    /**
     * @return array<int, array<'saml'|'kimai', string>>
     */
    public function getSamlAttributeMapping(): array
    {
        return $this->findArray('saml.mapping');
    }

    public function getSamlRolesAttribute(): ?string
    {
        $attr = $this->find('saml.roles.attribute');
        if (empty($attr)) {
            return null;
        }

        return (string) $attr;
    }

    public function isLdapActive(): bool
    {
        return (bool) $this->find('ldap.activate');
    }

    // ========== Calendar configurations ==========

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

    public function getCalendarGoogleSources(): array
    {
        return $this->findArray('calendar.google.sources');
    }

    public function getCalendarSlotDuration(): string
    {
        return (string) $this->find('calendar.slot_duration');
    }

    public function getCalendarDragAndDropMaxEntries(): int
    {
        return (int) $this->find('calendar.dragdrop_amount');
    }

    public function isCalendarDragAndDropCopyData(): bool
    {
        return (bool) $this->find('calendar.dragdrop_data');
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

    // TODO this is only used to display the hourly rate in the user profile
    public function getUserDefaultCurrency(): string
    {
        return $this->find('defaults.user.currency');
    }

    // ========== Timesheet configurations ==========
    /*
        public function getTimesheetBreakWarningDuration(): int
        {
            return (int) $this->find('timesheet.rules.break_warning_duration');
        }
    */
    public function getTimesheetLongRunningDuration(): int
    {
        return (int) $this->find('timesheet.rules.long_running_duration');
    }

    public function getTimesheetDefaultBeginTime(): string
    {
        return (string) $this->find('timesheet.default_begin');
    }

    public function isTimesheetAllowFutureTimes(): bool
    {
        return (bool) $this->find('timesheet.rules.allow_future_times');
    }

    public function isTimesheetAllowZeroDuration(): bool
    {
        return (bool) $this->find('timesheet.rules.allow_zero_duration');
    }

    public function isTimesheetAllowOverbookingBudget(): bool
    {
        return (bool) $this->find('timesheet.rules.allow_overbooking_budget');
    }

    public function isTimesheetAllowOverlappingRecords(): bool
    {
        return (bool) $this->find('timesheet.rules.allow_overlapping_records');
    }

    public function getTimesheetTrackingMode(): string
    {
        return $this->getString('timesheet.mode', 'default');
    }

    public function isTimesheetMarkdownEnabled(): bool
    {
        return (bool) $this->find('timesheet.markdown_content');
    }

    public function isTimesheetRequiresActivity(): bool
    {
        return (bool) $this->find('timesheet.rules.require_activity');
    }

    public function getTimesheetActiveEntriesHardLimit(): int
    {
        return (int) $this->find('timesheet.active_entries.hard_limit');
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

    public function getTimesheetIncrementDuration(): int
    {
        return $this->getIncrement('timesheet.duration_increment', $this->getTimesheetDefaultRoundingDuration(), 0);
    }

    public function getTimesheetIncrementMinutes(): int
    {
        return $this->getIncrement('timesheet.time_increment', $this->getTimesheetDefaultRoundingDuration(), 0);
    }

    public function getQuickEntriesRecentAmount(): int
    {
        return $this->getIncrement('quick_entry.recent_activities', 5, 0);
    }

    public function isBreakTimeEnabled(): bool
    {
        return (bool) $this->find('timesheet.rules.break_time_active');
    }

    // ========== Company configurations ==========

    public function getFinancialYearStart(): ?string
    {
        $start = $this->find('company.financial_year');

        if (empty($start)) {
            return null;
        }

        return (string) $start;
    }

    // ========== Theme configurations ==========

    public function isShowAbout(): bool
    {
        return (bool) $this->find('theme.show_about');
    }

    public function isThemeAllowAvatarUrls(): bool
    {
        return (bool) $this->find('theme.avatar_url');
    }

    /**
     * @internal will be made private soon after 2.18.0 - do not access this method directly, but through getThemeColors()
     */
    public function getThemeColorChoices(): string
    {
        $config = $this->find('theme.color_choices');
        if (\is_string($config) && $config !== '') {
            return $config;
        }

        return 'Silver|#c0c0c0';
    }

    /**
     * @return array<string, string>
     */
    public function getThemeColors(): array
    {
        $config = explode(',', $this->getThemeColorChoices());

        $colors = [];
        foreach ($config as $item) {
            if (empty($item)) {
                continue;
            }
            $item = explode('|', $item);
            $key = $item[0];
            $value = $key;

            if (\count($item) > 1) {
                $value = $item[1];
            }

            if (empty($key)) {
                $key = $value;
            }

            if ($value === Constants::DEFAULT_COLOR) {
                continue;
            }

            $colors[$key] = $value;
        }

        return array_unique($colors);
    }

    // ========== Projects ==========

    public function isProjectCopyTeamsOnCreate(): bool
    {
        return $this->find('project.copy_teams_on_create') === true;
    }

    // ========== Helper functions ==========

    private function getIncrement(string $key, int $fallback, int $min = 1): int
    {
        $config = $this->find($key);

        if ($config === null || trim($config) === '') {
            return $fallback;
        }

        $config = (int) $config;

        return max($config, $min);
    }

    private function getString(string $key, string $fallback): string
    {
        $config = $this->find($key);

        if ($config === null) {
            return $fallback;
        }

        $config = (string) $config;

        if (trim($config) === '') {
            return $fallback;
        }

        return $config;
    }
}
