<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model\Statistic;

use App\Model\Statistic\StatisticDate;
use DateTime;

/**
 * @covers \App\Model\Statistic\StatisticDate
 */
class StatisticDateTest extends AbstractTimesheetTest
{
    public function testDefaultValues(): void
    {
        $dateTime = new \DateTime('-8 hours');
        $sut = new StatisticDate($dateTime);
        $this->assertDefaultValues($sut);
        self::assertSame(0.0, $sut->getBillableRate());
        self::assertSame(0, $sut->getBillableDuration());
        self::assertNotSame($dateTime, $sut->getDate());
        self::assertEquals($dateTime->getTimestamp(), $sut->getDate()->getTimestamp());
    }

    public function testSetter(): void
    {
        $date = new DateTime('-8 hours');
        $sut = new StatisticDate($date);
        $this->assertSetter($sut);
    }

    public function testAdditionalMethods(): void
    {
        $date = new DateTime('-8 hours');
        $sut = new StatisticDate($date);

        $sut->setBillableRate(4869.38);
        self::assertSame(4869.38, $sut->getBillableRate());

        $sut->setBillableDuration(512376);
        self::assertSame(512376, $sut->getBillableDuration());
    }
}
