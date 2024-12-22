<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\SystemConfiguration;
use App\Entity\Configuration;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\SystemConfiguration
 */
class SystemConfigurationTest extends TestCase
{
    /**
     * @param array $settings
     * @param array $loaderSettings
     * @return SystemConfiguration
     */
    protected function getSut(array $settings, array $loaderSettings = []): SystemConfiguration
    {
        $loader = new TestConfigLoader($loaderSettings);

        return SystemConfigurationFactory::create($loader, $settings);
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function getDefaultSettings(): array
    {
        return [
            'timesheet' => [
                'rules' => [
                    'allow_future_times' => false,
                    'allow_zero_duration' => true,
                    'lockdown_period_start' => null,
                    'lockdown_period_end' => null,
                    'lockdown_grace_period' => null,
                ],
                'mode' => 'punch',
                'markdown_content' => false,
                'active_entries' => [
                    'hard_limit' => 99,
                ],
                'default_begin' => 'now',
                'duration_increment' => 10,
                'time_increment' => 5,
            ],
            'defaults' => [
                'customer' => [
                    'timezone' => 'Europe/London',
                    'currency' => 'GBP',
                    'country' => 'FR',
                ],
                'user' => [
                    'timezone' => 'foo/bar',
                    'theme' => 'blue',
                    'language' => 'IT',
                    'currency' => 'USD',
                ],
            ],
            'calendar' => [
                'businessHours' => [
                    'begin' => '07:49',
                    'end' => '19:27'
                ],
                'day_limit' => 20,
                'slot_duration' => '01:11:00',
                'week_numbers' => false,
                'visibleHours' => [
                    'begin' => '06:00:00',
                    'end' => '21:00:43',
                ],
                'google' => [
                    'api_key' => 'wertwertwegsdfbdf243w567fg8ihuon',
                    'sources' => [
                        'holidays' => [
                            'id' => 'de.german#holiday@group.v.calendar.google.com',
                            'color' => '#ccc',
                        ],
                        'holidays_en' => [
                            'id' => 'en.german#holiday@group.v.calendar.google.com',
                            'color' => '#fff',
                        ],
                    ]
                ],
                'weekends' => true,
            ],
            'saml' => [
                'activate' => false,
                'title' => 'Fantastic OAuth login'
            ],
            'theme' => [
                'color_choices' => 'Maroon|#800000,Brown|#a52a2a,Red|#ff0000,Orange|#ffa500,#ffffff,,|#000000',
                'colors_limited' => true,
                'branding' => [
                    'logo' => null,
                    'company' => 'Acme Corp.',
                ],
            ],
        ];
    }

    /**
     * @return array<Configuration>
     */
    protected function getDefaultLoaderSettings(): array
    {
        return [
            (new Configuration())->setName('defaults.customer.timezone')->setValue('Russia/Moscov'),
            (new Configuration())->setName('defaults.customer.currency')->setValue('RUB'),
            (new Configuration())->setName('calendar.slot_duration')->setValue('00:30:00'),
            (new Configuration())->setName('timesheet.rules.allow_future_times')->setValue('1'),
            (new Configuration())->setName('timesheet.rules.lockdown_period_start')->setValue('first day of last month'),
            (new Configuration())->setName('timesheet.rules.lockdown_period_end')->setValue('last day of last month'),
            (new Configuration())->setName('timesheet.rules.lockdown_grace_period')->setValue('+5 days'),
            (new Configuration())->setName('timesheet.mode')->setValue('default'),
            (new Configuration())->setName('timesheet.markdown_content')->setValue('1'),
            (new Configuration())->setName('timesheet.default_begin')->setValue('07:00'),
            (new Configuration())->setName('timesheet.active_entries.hard_limit')->setValue('7'),
        ];
    }

    public function testDefaultWithoutLoader(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        self::assertEquals('Europe/London', $sut->find('defaults.customer.timezone'));
        self::assertEquals('GBP', $sut->find('defaults.customer.currency'));
        self::assertFalse($sut->find('timesheet.rules.allow_future_times'));
        self::assertEquals(99, $sut->find('timesheet.active_entries.hard_limit'));
        self::assertEquals('Maroon|#800000,Brown|#a52a2a,Red|#ff0000,Orange|#ffa500,#ffffff,,|#000000', $sut->getThemeColorChoices());
        self::assertEquals(['Maroon' => '#800000', 'Brown' => '#a52a2a', 'Red' => '#ff0000', 'Orange' => '#ffa500', '#ffffff' => '#ffffff', '#000000' => '#000000'], $sut->getThemeColors());
    }

    public function testDefaultWithLoader(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        self::assertEquals('Russia/Moscov', $sut->find('defaults.customer.timezone'));
        self::assertEquals('RUB', $sut->find('defaults.customer.currency'));
        self::assertTrue($sut->find('timesheet.rules.allow_future_times'));
        self::assertEquals(7, $sut->find('timesheet.active_entries.hard_limit'));
        self::assertFalse($sut->isSamlActive());
    }

    public function testDefaultWithMixedConfigs(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.rules.allow_future_times')->setValue(''),
            (new Configuration())->setName('saml.activate')->setValue(true),
            (new Configuration())->setName('theme.color_choices')->setValue(''),
            (new Configuration())->setName('company.financial_year')->setValue('2020-03-27'),
        ]);
        self::assertFalse($sut->find('timesheet.rules.allow_future_times'));
        self::assertTrue($sut->isSamlActive());
        self::assertEquals('Silver|#c0c0c0', $sut->getThemeColorChoices());
        self::assertEquals(['Silver' => '#c0c0c0'], $sut->getThemeColors());
        self::assertEquals('2020-03-27', $sut->getFinancialYearStart());
    }

    public function testUnknownConfigs(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.foo')->setValue('hello'),
        ]);
        self::assertEquals('hello', $sut->find('timesheet.foo'));
        self::assertTrue($sut->has('timesheet.foo'));
        self::assertFalse($sut->has('timesheet.yyyyyyyyy'));
        self::assertFalse($sut->has('xxxxxxxx.yyyyyyyyy'));
        self::assertNull($sut->find('xxxxxxxx.yyyyyyyyy'));

        $sut->set('xxxxxxxx.yyyyyyyyy', 'foooo-bar!');
        self::assertTrue($sut->has('xxxxxxxx.yyyyyyyyy'));
        self::assertEquals('foooo-bar!', $sut->find('xxxxxxxx.yyyyyyyyy'));
    }

    public function testCalendarWithoutLoader(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        self::assertEquals('07:49', $sut->getCalendarBusinessTimeBegin());
        self::assertEquals('19:27', $sut->getCalendarBusinessTimeEnd());
        self::assertEquals('06:00:00', $sut->getCalendarTimeframeBegin());
        self::assertEquals('21:00:43', $sut->getCalendarTimeframeEnd());
        self::assertEquals('01:11:00', $sut->getCalendarSlotDuration());
        self::assertEquals(20, $sut->getCalendarDayLimit());
        self::assertFalse($sut->isCalendarShowWeekNumbers());
        self::assertTrue($sut->isCalendarShowWeekends());

        self::assertEquals('wertwertwegsdfbdf243w567fg8ihuon', $sut->getCalendarGoogleApiKey());
        $sources = $sut->getCalendarGoogleSources();
        self::assertEquals(2, \count($sources));
    }

    public function testCalendarWithLoader(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        self::assertEquals('00:30:00', $sut->getCalendarSlotDuration());
        $sources = $sut->getCalendarGoogleSources();
        self::assertEquals(2, \count($sources));
    }

    public function testFormDefaultWithoutLoader(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        self::assertEquals('Europe/London', $sut->getCustomerDefaultTimezone());
        self::assertEquals('GBP', $sut->getCustomerDefaultCurrency());
        self::assertEquals('FR', $sut->getCustomerDefaultCountry());
        self::assertEquals('foo/bar', $sut->getUserDefaultTimezone());
        self::assertEquals('blue', $sut->getUserDefaultTheme());
        self::assertEquals('IT', $sut->getUserDefaultLanguage());
        self::assertEquals('USD', $sut->getUserDefaultCurrency());
        self::assertNull($sut->getFinancialYearStart());
    }

    public function testFormDefaultWithLoader(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        self::assertEquals('Russia/Moscov', $sut->getCustomerDefaultTimezone());
        self::assertEquals('RUB', $sut->getCustomerDefaultCurrency());
        self::assertEquals('FR', $sut->getCustomerDefaultCountry());
        self::assertEquals('foo/bar', $sut->getUserDefaultTimezone());
        self::assertEquals('blue', $sut->getUserDefaultTheme());
        self::assertEquals('IT', $sut->getUserDefaultLanguage());
        self::assertEquals('USD', $sut->getUserDefaultCurrency());
    }

    public function testTimesheetWithoutLoader(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        self::assertEquals(99, $sut->getTimesheetActiveEntriesHardLimit());
        self::assertFalse($sut->isTimesheetAllowFutureTimes());
        self::assertFalse($sut->isTimesheetMarkdownEnabled());
        self::assertEquals('punch', $sut->getTimesheetTrackingMode());
        self::assertEquals('now', $sut->getTimesheetDefaultBeginTime());
        self::assertEquals('', $sut->isTimesheetAllowOverlappingRecords());
        self::assertEquals('', $sut->getTimesheetDefaultRoundingDays());
        self::assertEquals('', $sut->getTimesheetDefaultRoundingMode());
        self::assertEquals(0, $sut->getTimesheetDefaultRoundingDuration());
        self::assertEquals(0, $sut->getTimesheetDefaultRoundingEnd());
        self::assertEquals(0, $sut->getTimesheetDefaultRoundingBegin());
        self::assertEquals(10, $sut->getTimesheetIncrementDuration());
        self::assertEquals(5, $sut->getTimesheetIncrementMinutes());
    }

    public function testTimesheetWithLoader(): void
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        self::assertEquals(7, $sut->getTimesheetActiveEntriesHardLimit());
        self::assertTrue($sut->isTimesheetAllowFutureTimes());
        self::assertTrue($sut->isTimesheetMarkdownEnabled());
        self::assertEquals('default', $sut->getTimesheetTrackingMode());
        self::assertEquals('07:00', $sut->getTimesheetDefaultBeginTime());
        self::assertEquals('', $sut->isTimesheetAllowOverlappingRecords());
        self::assertEquals('', $sut->getTimesheetDefaultRoundingDays());
        self::assertEquals('', $sut->getTimesheetDefaultRoundingMode());
        self::assertEquals(0, $sut->getTimesheetDefaultRoundingDuration());
        self::assertEquals(0, $sut->getTimesheetDefaultRoundingEnd());
        self::assertEquals(0, $sut->getTimesheetDefaultRoundingBegin());
        self::assertEquals(10, $sut->getTimesheetIncrementDuration());
        self::assertEquals(5, $sut->getTimesheetIncrementMinutes());
    }
}
