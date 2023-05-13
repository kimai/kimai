<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model\Statistic;

use App\Model\Statistic\Month;
use App\Model\Statistic\Year;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\Statistic\Year
 */
class YearTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new Year('1999');

        self::assertNull($sut->getMonth(1));
        self::assertEmpty($sut->getMonths());
        self::assertIsArray($sut->getMonths());
        self::assertEquals('1999', $sut->getYear());
        self::assertSame(0.0, $sut->getRate());
        self::assertSame(0.0, $sut->getBillableRate());
        self::assertSame(0, $sut->getDuration());
        self::assertSame(0, $sut->getBillableDuration());
    }

    public function testSetter(): void
    {
        $sut = new Year('1999');

        $january = new Month('01');
        $january->setBillableDuration(1234);
        $january->setBillableRate(4321.12);
        $january->setTotalDuration(2345);
        $january->setTotalRate(5555.55);

        $february = new Month('02');
        $february->setBillableDuration(1234);
        $february->setBillableRate(4321.12);

        $march = new Month('03');
        $march->setBillableDuration(1);
        $march->setBillableRate(1.1);

        $sut->setMonth($january);
        $sut->setMonth($february);
        $sut->setMonth($march);
        self::assertEquals(3, \count($sut->getMonths()));

        $sut->setMonth(new Month('02'));

        self::assertEquals(3, \count($sut->getMonths()));

        self::assertInstanceOf(Month::class, $sut->getMonth(1));
        self::assertInstanceOf(Month::class, $sut->getMonth(2));
        self::assertInstanceOf(Month::class, $sut->getMonth(3));
        self::assertNull($sut->getMonth(4));

        self::assertSame(1235, $sut->getBillableDuration());
        self::assertSame(2345, $sut->getDuration());
        self::assertSame(4322.22, $sut->getBillableRate());
        self::assertSame(5555.55, $sut->getRate());
    }
}
