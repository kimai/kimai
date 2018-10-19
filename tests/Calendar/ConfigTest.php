<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\Config;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\Calendar\Config
 */
class ConfigTest extends TestCase
{
    public function testConstruct()
    {
        $config = [
            'businessHours' => [
                'days' => [0, 99, 127],
                'begin' => '07:49',
                'end' => '19:27'
            ],
            'initial_view' => 'foo_bar',
            'day_limit' => 20,
            'week_numbers' => false,
        ];

        $sut = new Config($config);

        $this->assertEquals([0, 99, 127], $sut->getBusinessDays());
        $this->assertEquals('07:49', $sut->getBusinessTimeBegin());
        $this->assertEquals('19:27', $sut->getBusinessTimeEnd());
        $this->assertEquals(20, $sut->getDayLimit());
        $this->assertEquals('foo_bar', $sut->getInitialView());
        $this->assertFalse($sut->isShowWeekNumbers());
    }
}
