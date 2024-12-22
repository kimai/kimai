<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model\Statistic;

use App\Model\Statistic\Month;
use InvalidArgumentException;

/**
 * @covers \App\Model\Statistic\Month
 */
class MonthTest extends AbstractTimesheetTestCase
{
    public function testDefaultValues(): void
    {
        $sut = new Month('01');
        $this->assertDefaultValues($sut);

        self::assertSame('01', $sut->getMonth());
        self::assertSame(0, $sut->getTotalDuration());
        self::assertSame(0.0, $sut->getTotalRate());
        self::assertSame(0, $sut->getBillableDuration());
        self::assertSame(0.0, $sut->getBillableRate());
    }

    public static function getTestData()
    {
        yield ['01', '01', 1];
        yield ['02', '02', 2];
        yield ['03', '03', 3];
        yield ['04', '04', 4];
        yield ['05', '05', 5];
        yield ['06', '06', 6];
        yield ['07', '07', 7];
        yield ['08', '08', 8];
        yield ['09', '09', 9];
        yield ['10', '10', 10];
        yield ['11', '11', 11];
        yield ['12', '12', 12];
        yield [1, '01', 1];
        yield [2, '02', 2];
        yield [3, '03', 3];
        yield [4, '04', 4];
        yield [5, '05', 5];
        yield [6, '06', 6];
        yield [7, '07', 7];
        yield [8, '08', 8];
        yield [9, '09', 9];
        yield [10, '10', 10];
        yield [11, '11', 11];
        yield [12, '12', 12];
    }

    /**
     * @dataProvider getTestData
     */
    public function testAllowedMonths($init, $month, $number): void
    {
        $sut = new Month($init);
        self::assertEquals($month, $sut->getMonth());
        self::assertEquals($number, $sut->getMonthNumber());
    }

    public static function getInvalidTestData()
    {
        yield ['00'];
        yield ['13'];
        yield ['99'];
        yield ['0.9'];
        yield [19];
    }

    /**
     * @dataProvider getInvalidTestData
     */
    public function testInvalidMonths($month): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid month given. Expected 1-12, received "' . ((int) $month) . '".');
        new Month($month);
    }

    public function testSetter(): void
    {
        $sut = new Month('01');
        $this->assertSetter($sut);

        $sut->setTotalDuration(999);
        $sut->setTotalRate(0.123456789);
        $sut->setBillableDuration(123456);
        $sut->setBillableRate(123.456789);

        self::assertSame(999, $sut->getTotalDuration());
        self::assertSame(0.123456789, $sut->getTotalRate());
        self::assertSame(123456, $sut->getBillableDuration());
        self::assertSame(123.456789, $sut->getBillableRate());
    }
}
