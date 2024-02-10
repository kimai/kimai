<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\User;
use App\Model\DailyStatistic;
use App\Model\Statistic\StatisticDate;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\DailyStatistic
 */
class DailyStatisticTest extends TestCase
{
    public function testStatistic(): void
    {
        $begin = new \DateTime('2018-04-07 12:00:00');
        $end = new \DateTime('2018-04-13 18:00:00');
        $user = new User();
        $sut = new DailyStatistic($begin, $end, $user);

        self::assertSame($user, $sut->getUser());
        $days = $sut->getDays();
        $dateTimes = $sut->getDateTimes();

        self::assertCount(7, $days);
        self::assertCount(7, $dateTimes);

        $expectedDateTimes = [
            new \DateTime('2018-04-07 00:00:00'),
            new \DateTime('2018-04-08 00:00:00'),
            new \DateTime('2018-04-09 00:00:00'),
            new \DateTime('2018-04-10 00:00:00'),
            new \DateTime('2018-04-11 00:00:00'),
            new \DateTime('2018-04-12 00:00:00'),
            new \DateTime('2018-04-13 00:00:00'),
        ];

        self::assertEquals($expectedDateTimes, $dateTimes);

        foreach ($days as $day) {
            self::assertInstanceOf(StatisticDate::class, $day);
        }

        self::assertInstanceOf(StatisticDate::class, $sut->getDayByDateTime(new \DateTime('2018-04-13 23:12:00')));
        self::assertInstanceOf(StatisticDate::class, $sut->getDay('2018', '4', '13'));
        self::assertInstanceOf(StatisticDate::class, $sut->getDay('2018', '04', '13'));
        self::assertInstanceOf(StatisticDate::class, $sut->getDay('2018', '4', '7'));
        self::assertInstanceOf(StatisticDate::class, $sut->getDay('2018', '4', '07'));
        self::assertInstanceOf(StatisticDate::class, $sut->getDay('2018', '04', '7'));
        self::assertInstanceOf(StatisticDate::class, $sut->getDayByReportDate('2018-04-13'));

        self::assertNull($sut->getDayByDateTime(new \DateTime('2018-04-06 23:59:59')));
        self::assertNull($sut->getDayByDateTime(new \DateTime('2018-04-14 00:00:00')));
        self::assertNull($sut->getDay('2018', '4', '06'));
        self::assertNull($sut->getDay('2018', '4', '6'));
        self::assertNull($sut->getDay('2018', '4', '14'));
        self::assertNull($sut->getDay('2018', '04', '14'));
        self::assertNull($sut->getDayByReportDate('2018-04-14'));
        self::assertNull($sut->getDayByReportDate('2018-4-14'));
    }
}
