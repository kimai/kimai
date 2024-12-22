<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model\Statistic;

use App\Model\Statistic\Day;
use DateTime;

/**
 * @covers \App\Model\Statistic\Day
 */
class DayTest extends AbstractTimesheetTestCase
{
    public function testDefaultValues(): void
    {
        $date = new DateTime('-8 hours');
        $sut = new Day($date, 0, 0.0);
        $this->assertDefaultValues($sut);
    }

    public function testSetter(): void
    {
        $date = new DateTime('-8 hours');
        $sut = new Day($date, 12340, 197.25956);
        $this->assertSetter($sut);
    }

    public function testConstruct(): void
    {
        $date = new DateTime('-8 hours');
        $sut = new Day($date, 12340, 197.25956);

        self::assertSame($date, $sut->getDay());
        self::assertEquals([], $sut->getDetails());
        self::assertSame(12340, $sut->getTotalDuration());
        self::assertSame(197.25956, $sut->getTotalRate());
        self::assertSame(0, $sut->getTotalDurationBillable());
    }

    public function testAllowedMonths(): void
    {
        $date = new DateTime('-8 hours');
        $sut = new Day($date, 12340, 197.25956);

        $sut->setTotalDuration(999);
        $sut->setTotalRate(0.123456789);
        $sut->setTotalDurationBillable(12345);

        self::assertSame(999, $sut->getTotalDuration());
        self::assertSame(0.123456789, $sut->getTotalRate());
        self::assertSame(12345, $sut->getTotalDurationBillable());
    }

    public function testSetDetails(): void
    {
        $sut = new Day(new DateTime(), 12340, 197.25956);

        $sut->setDetails(['foo' => ['bar' => '1212e'], 'hello' => 'world']);

        self::assertEquals(['foo' => ['bar' => '1212e'], 'hello' => 'world'], $sut->getDetails());
    }
}
