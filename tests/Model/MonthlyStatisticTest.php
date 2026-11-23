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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MonthlyStatistic::class)]
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
            new \DateTime('2017-04-01 00:00:00'),
            new \DateTime('2017-05-01 00:00:00'),
            new \DateTime('2017-06-01 00:00:00'),
            new \DateTime('2017-07-01 00:00:00'),
            new \DateTime('2017-08-01 00:00:00'),
            new \DateTime('2017-09-01 00:00:00'),
            new \DateTime('2017-10-01 00:00:00'),
            new \DateTime('2017-11-01 00:00:00'),
            new \DateTime('2017-12-01 00:00:00'),
            new \DateTime('2018-01-01 00:00:00'),
            new \DateTime('2018-02-01 00:00:00'),
            new \DateTime('2018-03-01 00:00:00'),
            new \DateTime('2018-04-01 00:00:00'),
            new \DateTime('2018-05-01 00:00:00'),
            new \DateTime('2018-06-01 00:00:00'),
            new \DateTime('2018-07-01 00:00:00'),
            new \DateTime('2018-08-01 00:00:00'),
            new \DateTime('2018-09-01 00:00:00'),
            new \DateTime('2018-10-01 00:00:00'),
            new \DateTime('2018-11-01 00:00:00'),
            new \DateTime('2018-12-01 00:00:00'),
            new \DateTime('2019-01-01 00:00:00'),
            new \DateTime('2019-02-01 00:00:00'),
            new \DateTime('2019-03-01 00:00:00'),
            new \DateTime('2019-04-01 00:00:00'),
            new \DateTime('2019-05-01 00:00:00'),
            new \DateTime('2019-06-01 00:00:00'),
            new \DateTime('2019-07-01 00:00:00'),
            new \DateTime('2019-08-01 00:00:00'),
            new \DateTime('2019-09-01 00:00:00'),
            new \DateTime('2019-10-01 00:00:00'),
            new \DateTime('2019-11-01 00:00:00'),
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

    public function testRangeStartingAtTheLastDayOfAMonth(): void
    {
        // a range starting on the 31st must not overflow into the following month,
        // which resulted in duplicated (eg. two times "March") and missing (eg. "September") columns
        $begin = new \DateTime('2025-12-31 00:00:00');
        $end = new \DateTime('2026-12-30 23:59:59');
        $sut = new MonthlyStatistic($begin, $end, new User());

        self::assertEquals(['2025', '2026'], $sut->getYears());
        self::assertCount(13, $sut->getMonths());

        $expectedDateTimes = [
            new \DateTime('2025-12-01 00:00:00'),
            new \DateTime('2026-01-01 00:00:00'),
            new \DateTime('2026-02-01 00:00:00'),
            new \DateTime('2026-03-01 00:00:00'),
            new \DateTime('2026-04-01 00:00:00'),
            new \DateTime('2026-05-01 00:00:00'),
            new \DateTime('2026-06-01 00:00:00'),
            new \DateTime('2026-07-01 00:00:00'),
            new \DateTime('2026-08-01 00:00:00'),
            new \DateTime('2026-09-01 00:00:00'),
            new \DateTime('2026-10-01 00:00:00'),
            new \DateTime('2026-11-01 00:00:00'),
            new \DateTime('2026-12-01 00:00:00'),
        ];

        self::assertEquals($expectedDateTimes, $sut->getDateTimes());

        // every month of the range has to exist exactly once
        for ($i = 1; $i < 13; $i++) {
            self::assertInstanceOf(StatisticDate::class, $sut->getMonth('2026', (string) $i));
        }
        self::assertInstanceOf(StatisticDate::class, $sut->getMonth('2025', '12'));
        self::assertNull($sut->getMonth('2025', '11'));
    }

    public function testRangeKeepsPartialLastMonth(): void
    {
        $begin = new \DateTime('2020-04-15 00:00:00');
        $end = new \DateTime('2021-04-14 23:59:59');
        $sut = new MonthlyStatistic($begin, $end, new User());

        self::assertCount(13, $sut->getMonths());

        $year = $sut->getYear('2020');
        self::assertIsArray($year);
        self::assertCount(9, $year);

        $year = $sut->getYear('2021');
        self::assertIsArray($year);
        self::assertCount(4, $year);

        // the first and the last month are only partially included, but must not be dropped
        $month = $sut->getMonth('2021', '4');
        self::assertInstanceOf(StatisticDate::class, $month);
        self::assertEquals(new \DateTime('2021-04-01 00:00:00'), $month->getDate());

        $month = $sut->getMonth('2020', '4');
        self::assertInstanceOf(StatisticDate::class, $month);
        self::assertEquals(new \DateTime('2020-04-01 00:00:00'), $month->getDate());
    }

    public function testWithBeginAndEndInTheSameMonth(): void
    {
        $sut = new MonthlyStatistic(new \DateTime('2026-03-05 10:00:00'), new \DateTime('2026-03-20 18:00:00'), new User());

        self::assertCount(1, $sut->getMonths());
        self::assertEquals([new \DateTime('2026-03-01 00:00:00')], $sut->getDateTimes());
    }

    public function testWithEndBeforeBegin(): void
    {
        $sut = new MonthlyStatistic(new \DateTime('2026-03-05 00:00:00'), new \DateTime('2026-02-01 00:00:00'), new User());

        self::assertEquals([], $sut->getYears());
        self::assertEquals([], $sut->getMonths());
        self::assertEquals([], $sut->getDateTimes());
        self::assertNull($sut->getMonth('2026', '3'));
    }
}
