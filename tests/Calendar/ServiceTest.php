<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\Config;
use App\Calendar\Google;
use App\Calendar\Service;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\Calendar\Service
 */
class ServiceTest extends TestCase
{
    public function testConstruct()
    {
        $config = [
            'businessHours' => [
                'days' => [2, 4, 6],
                'begin' => '07:49',
                'end' => '19:27'
            ],
            'day_limit' => 20,
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
            ]
        ];

        $sut = new Service($config);

        $config = $sut->getConfig();
        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals([2, 4, 6], $config->getBusinessDays());

        $google = $sut->getGoogle();
        $this->assertInstanceOf(Google::class, $google);
        $this->assertEquals('wertwertwegsdfbdf243w567fg8ihuon', $google->getApiKey());

        $sources = $google->getSources();
        $this->assertEquals(2, count($sources));
        $this->assertEquals('holidays', $sources[0]->getId());
        $this->assertEquals('#ccc', $sources[0]->getColor());
        $this->assertEquals('holidays_en', $sources[1]->getId());
        $this->assertEquals('#fff', $sources[1]->getColor());
    }
}
