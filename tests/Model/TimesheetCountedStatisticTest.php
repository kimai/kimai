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
        self::assertEquals(0.0, $sut->getRecordRate());
        self::assertEquals(0, $sut->getRecordDuration());
        self::assertEquals(0, $sut->getRecordAmount());
        self::assertEquals(0.0, $sut->getRecordInternalRate());
    }

    public function testSetter()
    {
        $sut = new TimesheetCountedStatistic();
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordRate(23.97));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordDuration(21));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordAmount(5));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordInternalRate(99.09));

        self::assertEquals(23.97, $sut->getRecordRate());
        self::assertEquals(21, $sut->getRecordDuration());
        self::assertEquals(5, $sut->getRecordAmount());
        self::assertEquals(99.09, $sut->getRecordInternalRate());
    }
}
