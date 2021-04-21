<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\TimesheetCountedStatistic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\TimesheetCountedStatistic
 */
class TimesheetCountedStatisticTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new TimesheetCountedStatistic();
        self::assertSame(0.0, $sut->getRecordRate());
        self::assertSame(0, $sut->getRecordDuration());
        self::assertSame(0, $sut->getRecordAmount());
        self::assertSame(0.0, $sut->getRecordInternalRate());
        self::assertSame(0, $sut->getDurationBillable());
        self::assertSame(0.0, $sut->getRateBillable());
        self::assertSame(0, $sut->getRecordAmountBillable());
    }

    public function testSetter()
    {
        $sut = new TimesheetCountedStatistic();
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordRate(23.97));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordDuration(21));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordAmount(5));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordInternalRate(99.09));

        self::assertSame(23.97, $sut->getRecordRate());
        self::assertSame(21, $sut->getRecordDuration());
        self::assertSame(5, $sut->getRecordAmount());
        self::assertSame(99.09, $sut->getRecordInternalRate());

        $sut->setRateBillable(123.456);
        $sut->setDurationBillable(1234);
        $sut->setRecordAmountBillable(4321);

        self::assertSame(123.456, $sut->getRateBillable());
        self::assertSame(1234, $sut->getDurationBillable());
        self::assertSame(4321, $sut->getRecordAmountBillable());
    }
}
