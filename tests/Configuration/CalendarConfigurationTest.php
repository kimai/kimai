<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\CalendarConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\CalendarConfiguration
 * @covers \App\Configuration\StringAccessibleConfigTrait
 */
class CalendarConfigurationTest extends TestCase
{
    /**
     * @param array $settings
     * @param array $loaderSettings
     * @return CalendarConfiguration
     */
    protected function getSut(array $settings, array $loaderSettings = [])
    {
        $loader = new TestConfigLoader($loaderSettings);

        return new CalendarConfiguration($loader, $settings);
    }

    /**
     * @return array
     */
    protected function getDefaultSettings()
    {
        return [
            'businessHours' => [
                'days' => [2, 4, 6],
                'begin' => '07:49',
                'end' => '19:27'
            ],
            'day_limit' => 20,
            'slot_duration' => '01:11:00',
            'week_numbers' => false,
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
        ];
    }

    public function testPrefix()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('calendar', $sut->getPrefix());
    }

    public function testConfigs()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals([2, 4, 6], $sut->getBusinessDays());
        $this->assertEquals('07:49', $sut->getBusinessTimeBegin());
        $this->assertEquals('19:27', $sut->getBusinessTimeEnd());
        $this->assertEquals('01:11:00', $sut->getSlotDuration());
        $this->assertEquals(20, $sut->getDayLimit());
        $this->assertFalse($sut->isShowWeekNumbers());

        $this->assertEquals('wertwertwegsdfbdf243w567fg8ihuon', $sut->getGoogleApiKey());
        $sources = $sut->getGoogleSources();
        $this->assertEquals(2, \count($sources));
    }
}
