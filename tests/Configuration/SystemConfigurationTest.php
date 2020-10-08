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
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\SystemConfiguration
 * @covers \App\Configuration\StringAccessibleConfigTrait
 */
class SystemConfigurationTest extends TestCase
{
    /**
     * @param array $settings
     * @param array $loaderSettings
     * @return SystemConfiguration
     */
    protected function getSut(array $settings, array $loaderSettings = [])
    {
        $loader = new TestConfigLoader($loaderSettings);

        return new SystemConfiguration($loader, $settings);
    }

    protected function getDefaultSettings()
    {
        return [
            'timesheet' => [
                'rules' => [
                    'allow_future_times' => false,
                ],
                'mode' => 'duration_only',
                'markdown_content' => false,
                'active_entries' => [
                    'hard_limit' => 99,
                    'soft_limit' => 15,
                ],
            ],
            'defaults' => [
                'customer' => [
                    'timezone' => 'Europe/London',
                    'currency' => 'GBP',
                    'country' => 'FR',
                ],
            ],
            'calendar' => [
                'businessHours' => [
                    'days' => [2, 4, 6],
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
            ]
        ];
    }

    protected function getDefaultLoaderSettings()
    {
        return [
            (new Configuration())->setName('defaults.customer.timezone')->setValue('Russia/Moscov'),
            (new Configuration())->setName('defaults.customer.currency')->setValue('RUB'),
            (new Configuration())->setName('timesheet.rules.allow_future_times')->setValue('1'),
            (new Configuration())->setName('timesheet.mode')->setValue('default'),
            (new Configuration())->setName('timesheet.markdown_content')->setValue('1'),
            (new Configuration())->setName('timesheet.active_entries.hard_limit')->setValue('7'),
            (new Configuration())->setName('timesheet.active_entries.soft_limit')->setValue('3'),
            (new Configuration())->setName('calendar.slot_duration')->setValue('00:30:00'),
        ];
    }

    public function testPrefix()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('kimai', $sut->getPrefix());
    }

    public function testDefaultWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('Europe/London', $sut->find('defaults.customer.timezone'));
        $this->assertEquals('GBP', $sut->find('defaults.customer.currency'));
        $this->assertEquals(false, $sut->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(99, $sut->find('timesheet.active_entries.hard_limit'));
    }

    public function testDefaultWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals('Russia/Moscov', $sut->find('defaults.customer.timezone'));
        $this->assertEquals('RUB', $sut->find('defaults.customer.currency'));
        $this->assertEquals(true, $sut->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(7, $sut->find('timesheet.active_entries.hard_limit'));
    }

    public function testDefaultWithMixedConfigs()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.rules.allow_future_times')->setValue(''),
        ]);
        $this->assertEquals(false, $sut->find('timesheet.rules.allow_future_times'));
    }

    public function testUnknownConfigs()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.foo')->setValue('hello'),
        ]);
        $this->assertEquals('hello', $sut->find('timesheet.foo'));
        $this->assertFalse($sut->has('xxxxxxxx.yyyyyyyyy'));
        $this->assertNull($sut->find('xxxxxxxx.yyyyyyyyy'));
    }

    public function testCalendarWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals([2, 4, 6], $sut->getCalendarBusinessDays());
        $this->assertEquals('07:49', $sut->getCalendarBusinessTimeBegin());
        $this->assertEquals('19:27', $sut->getCalendarBusinessTimeEnd());
        $this->assertEquals('06:00:00', $sut->getCalendarTimeframeBegin());
        $this->assertEquals('21:00:43', $sut->getCalendarTimeframeEnd());
        $this->assertEquals('01:11:00', $sut->getCalendarSlotDuration());
        $this->assertEquals(20, $sut->getCalendarDayLimit());
        $this->assertFalse($sut->isCalendarShowWeekNumbers());
        $this->assertTrue($sut->isCalendarShowWeekends());

        $this->assertEquals('wertwertwegsdfbdf243w567fg8ihuon', $sut->getCalendarGoogleApiKey());
        $sources = $sut->getCalendarGoogleSources();
        $this->assertEquals(2, \count($sources));
    }

    public function testCalendarWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals('00:30:00', $sut->getCalendarSlotDuration());
        $sources = $sut->getCalendarGoogleSources();
        $this->assertEquals(2, \count($sources));
    }
}
