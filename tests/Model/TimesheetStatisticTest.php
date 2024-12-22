<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\TimesheetStatistic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\TimesheetStatistic
 */
class TimesheetStatisticTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new TimesheetStatistic();
        self::assertEquals(0, $sut->getRecordsTotal());
        self::assertEquals(0, $sut->getAmountTotal());
        self::assertEquals(0, $sut->getAmountThisMonth());
        self::assertEquals(0, $sut->getDurationTotal());
        self::assertEquals(0, $sut->getDurationThisMonth());
    }

    public function testSetter(): void
    {
        $sut = new TimesheetStatistic();
        $sut->setRecordsTotal(2);
        $sut->setAmountTotal(7654.298);
        $sut->setAmountThisMonth(826.10);
        $sut->setDurationTotal(13);
        $sut->setDurationThisMonth(200);

        self::assertEquals(2, $sut->getRecordsTotal());
        self::assertEquals(7654.298, $sut->getAmountTotal());
        self::assertEquals(826.10, $sut->getAmountThisMonth());
        self::assertEquals(13, $sut->getDurationTotal());
        self::assertEquals(200, $sut->getDurationThisMonth());
    }
}
