<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\User;
use App\Model\MonthlyStatistic;
use App\Model\Statistic\StatisticDate;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\MonthlyStatistic
 */
class MonthlyStatisticTest extends TestCase
{
    public function testStatistic(): void
    {
        $begin = new \DateTime('2017-04-07 12:00:00');
        $end = new \DateTime('2019-11-13 18:00:00');
        $user = new User();
        $sut = new MonthlyStatistic($begin, $end, $user);

        self::assertSame($user, $sut->getUser());
        $years = $sut->getYears();
        $months = $sut->getMonths();
        $dateTimes = $sut->getDateTimes();

        self::assertCount(32, $months);
        self::assertCount(32, $dateTimes);
        self::assertCount(3, $years);

        $expectedDateTimes = [
            new \DateTime('2017-04-07 00:00:00'),
            new \DateTime('2017-05-07 00:00:00'),
            new \DateTime('2017-06-07 00:00:00'),
            new \DateTime('2017-07-07 00:00:00'),
            new \DateTime('2017-08-07 00:00:00'),
            new \DateTime('2017-09-07 00:00:00'),
            new \DateTime('2017-10-07 00:00:00'),
            new \DateTime('2017-11-07 00:00:00'),
            new \DateTime('2017-12-07 00:00:00'),
            new \DateTime('2018-01-07 00:00:00'),
            new \DateTime('2018-02-07 00:00:00'),
            new \DateTime('2018-03-07 00:00:00'),
            new \DateTime('2018-04-07 00:00:00'),
            new \DateTime('2018-05-07 00:00:00'),
            new \DateTime('2018-06-07 00:00:00'),
            new \DateTime('2018-07-07 00:00:00'),
            new \DateTime('2018-08-07 00:00:00'),
            new \DateTime('2018-09-07 00:00:00'),
            new \DateTime('2018-10-07 00:00:00'),
            new \DateTime('2018-11-07 00:00:00'),
            new \DateTime('2018-12-07 00:00:00'),
            new \DateTime('2019-01-07 00:00:00'),
            new \DateTime('2019-02-07 00:00:00'),
            new \DateTime('2019-03-07 00:00:00'),
            new \DateTime('2019-04-07 00:00:00'),
            new \DateTime('2019-05-07 00:00:00'),
            new \DateTime('2019-06-07 00:00:00'),
            new \DateTime('2019-07-07 00:00:00'),
            new \DateTime('2019-08-07 00:00:00'),
            new \DateTime('2019-09-07 00:00:00'),
            new \DateTime('2019-10-07 00:00:00'),
            new \DateTime('2019-11-07 00:00:00'),
        ];

        self::assertEquals($expectedDateTimes, $dateTimes);

        $expectedYears = [
            '2017',
            '2018',
            '2019',
        ];

        self::assertEquals($expectedYears, $years);

        foreach ($months as $month) {
            self::assertInstanceOf(StatisticDate::class, $month);
        }

        $year = $sut->getYear('2017');
        self::assertCount(9, $year);
        foreach ($year as $month) {
            self::assertInstanceOf(StatisticDate::class, $month);
        }

        self::assertInstanceOf(StatisticDate::class, $sut->getMonth('2017', '4'));
        self::assertInstanceOf(StatisticDate::class, $sut->getMonth('2019', '11'));
        self::assertInstanceOf(StatisticDate::class, $sut->getMonth('2018', '4'));
        self::assertInstanceOf(StatisticDate::class, $sut->getMonth('2018', '04'));

        self::assertNull($sut->getYear('2016'));
        self::assertNull($sut->getYear('2020'));
        self::assertNull($sut->getMonth('2018', '14'));
        self::assertNull($sut->getMonth('2017', '14'));
        self::assertNull($sut->getMonth('2017', '3'));
        self::assertNull($sut->getMonth('2017', '03'));
        self::assertNull($sut->getMonth('2019', '12'));
        self::assertNull($sut->getMonth('2020', '1'));
        self::assertNull($sut->getMonth('2020', '01'));
        self::assertNull($sut->getMonthByDateTime(new \DateTime('2020-01-01')));
        self::assertInstanceOf(StatisticDate::class, $sut->getMonthByDateTime(new \DateTime('2018-04-01')));
        self::assertInstanceOf(StatisticDate::class, $sut->getByDateTime(new \DateTime('2018-04-01')));

        self::assertSame($sut->getMonths(), $sut->getData());
    }
}
