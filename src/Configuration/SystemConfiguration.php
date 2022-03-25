<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Entity\Configuration;

/**
 * @final soft final for now only because some tests need to be adjusted: you have been warned...
 */
class SystemConfiguration
{
    private ?array $settings;
    private array $original;
    private ConfigLoaderInterface $repository;
    private bool $initialized = false;

    public function __construct(ConfigLoaderInterface $repository, array $settings)
    {
        $this->repository = $repository;
        $this->original = $this->settings = $settings;
    }

    /**
     * @param ConfigLoaderInterface $repository
     * @return Configuration[]
     */
    private function getConfigurations(ConfigLoaderInterface $repository): array
    {
        return $repository->getConfiguration();
    }

    private function prepare()
    {
        if ($this->initialized) {
            return;
        }

        // this foreach should be replaced by a better piece of code,
        // especially the pointers could be a problem in the future
        foreach ($this->getConfigurations($this->repository) as $configuration) {
            $temp = explode('.', $configuration->getName());
            $this->setConfiguration($temp, $configuration->getValue());
        }

        $this->initialized = true;
    }

    private function setConfiguration(array $keys, null|string|int|bool $value): void
    {
        $array = &$this->settings;
        if ($keys[0] === $this->getPrefix()) {
            $keys = \array_slice($keys, 1);
        }
        foreach ($keys as $key2) {
            if (!\array_key_exists($key2, $array)) {
                $array[$key2] = $value;
                continue;
            }
            if (\is_array($array[$key2])) {
                $array = &$array[$key2];
            } elseif (\is_bool($array[$key2])) {
                $array[$key2] = (bool) $value;
            } elseif (\is_int($array[$key2])) {
                $array[$key2] = (int) $value;
            } else {
                $array[$key2] = $value;
            }
        }
    }

    public function default(string $key): mixed
    {
        $key = $this->prepareSearchKey($key);

        return $this->get($key, $this->original);
    }

    /**
     * @param string $key
     * @return string|int|bool|float|null|array
     */
    public function find(string $key)
    {
        $this->prepare();
        $key = $this->prepareSearchKey($key);

        return $this->get($key, $this->settings);
    }

    private function prepareSearchKey(string $key): string
    {
        $prefix = $this->getPrefix() . '.';
        $length = \strlen($prefix);

        if (substr($key, 0, $length) === $prefix) {
            $key = substr($key, $length);
        }

        return $key;
    }

    /**
     * @param string $key
     * @param array $config
     * @return mixed
     */
    private function get(string $key, array $config)
    {
        $keys = explode('.', $key);
        $search = array_shift($keys);

        if (!\array_key_exists($search, $config)) {
            return null;
        }

        if (\is_array($config[$search]) && !empty($keys)) {
            return $this->get(implode('.', $keys), $config[$search]);
        }

        return $config[$search];
    }

    public function has(string $key): bool
    {
        $this->prepare();
        $key = $this->prepareSearchKey($key);

        $keys = explode('.', $key);
        $search = array_shift($keys);

        if (!\array_key_exists($search, $this->settings)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->find($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        $this->setConfiguration(explode('.', $offset), $value);
    }

    /**
     * @param mixed $offset
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('SystemBundleConfiguration does not support offsetUnset()');
    }

    public function getPrefix(): string
    {
        return 'kimai';
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

    public function getSamlProvider(): ?string
    {
        return $this->find('saml.provider');
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

    public function isShowAbout(): bool
    {
        return (bool) $this->find('theme.show_about');
    }

    public function getThemeBundle(): string
    {
        return 'Tabler';
    }

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
}
