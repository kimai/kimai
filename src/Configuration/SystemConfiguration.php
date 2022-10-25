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

    // ========== Login form ==========

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
        return (bool) $this->find('user.password_reset');
    }

    // ========== SAML configurations ==========

    public function isSamlActive(): bool
    {
        return (bool) $this->find('saml.activate');
    }

    public function getSamlTitle(): string
    {
        return (string) $this->find('saml.title');
    }

    public function getSamlAttributeMapping(): array
    {
        return (array) $this->find('saml.mapping');
    }

    public function getSamlRolesAttribute(): ?string
    {
        return (string) $this->find('saml.roles.attribute');
    }

    public function getSamlRolesMapping(): array
    {
        return (array) $this->find('saml.roles.mapping');
    }

    public function isSamlRolesResetOnLogin(): bool
    {
        return (bool) $this->find('saml.roles.resetOnLogin');
    }

    public function getSamlConnection(): array
    {
        return (array) $this->find('saml.connection');
    }

    // ========== LDAP configurations ==========

    public function isLdapActive(): bool
    {
        return (bool) $this->find('ldap.activate');
    }

    public function getLdapRoleParameters(): array
    {
        return (array) $this->find('ldap.role');
    }

    public function getLdapUserParameters(): array
    {
        return (array) $this->find('ldap.user');
    }

    public function getLdapConnectionParameters(): array
    {
        return (array) $this->find('ldap.connection');
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
        @trigger_error('The configuration timesheet.active_entries.soft_limit is deprecated since 1.15', E_USER_DEPRECATED);

        return $this->getTimesheetActiveEntriesHardLimit();
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

    public function getTimesheetLockdownTimeZone(): ?string
    {
        return $this->find('timesheet.rules.lockdown_period_timezone');
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

    public function getQuickEntriesRecentAmount(): int
    {
        return $this->getIncrement('quick_entry.recent_activities', 5, 0) ?? 5;
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

    public function isThemeColorsLimited(): bool
    {
        return (bool) $this->find('theme.colors_limited');
    }

    public function isThemeRandomColors(): bool
    {
        return (bool) $this->find('theme.random_colors');
    }

    public function isThemeAllowAvatarUrls(): bool
    {
        return (bool) $this->find('theme.avatar_url');
    }

    public function getThemeAutocompleteCharacters(): int
    {
        return (int) $this->find('theme.autocomplete_chars');
    }

    public function getThemeColorChoices(): ?string
    {
        $config = $this->find('theme.color_choices');
        if (!empty($config)) {
            return $config;
        }

        return $this->default('theme.color_choices');
    }

    // ========== Branding configurations ==========

    public function getBrandingTitle(): ?string
    {
        return $this->find('theme.branding.title');
    }

    public function isAllowTagCreation(): bool
    {
        return (bool) $this->find('theme.tags_create');
    }

    // ========== Projects ==========

    public function isProjectCopyTeamsOnCreate(): bool
    {
        return $this->find('project.copy_teams_on_create') === true;
    }
}
