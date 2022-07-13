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

/**
 * @covers \App\Model\Statistic\Year
 */
class YearTest extends AbstractTimesheetTest
{
    public function testDefaultValues()
    {
        $sut = new Year('1999');
        $this->assertDefaultValues($sut);

        self::assertNull($sut->getMonth(1));
        self::assertEmpty($sut->getMonths());
        self::assertIsArray($sut->getMonths());
        self::assertEquals('1999', $sut->getYear());
        self::assertSame(0, $sut->getBillableDuration());
        self::assertSame(0.0, $sut->getBillableRate());
    }

    public function testSetter()
    {
        $sut = new Year('1999');
        $this->assertSetter($sut);

        $sut->setMonth(new Month('01'));
        $sut->setMonth(new Month('02'));
        $sut->setMonth(new Month('03'));
        self::assertEquals(3, \count($sut->getMonths()));

        $sut->setMonth(new Month('01'));

        self::assertEquals(3, \count($sut->getMonths()));

        self::assertInstanceOf(Month::class, $sut->getMonth(1));
        self::assertInstanceOf(Month::class, $sut->getMonth(2));
        self::assertInstanceOf(Month::class, $sut->getMonth(3));
        self::assertNull($sut->getMonth(4));

        $sut->setBillableDuration(123456);
        $sut->setBillableRate(123.456789);
        self::assertSame(123456, $sut->getBillableDuration());
        self::assertSame(123.456789, $sut->getBillableRate());
    }
}
