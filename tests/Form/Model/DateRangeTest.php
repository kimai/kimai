<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Model;

use App\Form\Model\DateRange;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\Model\DateRange
 */
class DateRangeTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new DateRange();
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());
    }

    public function testSetterAndGetter(): void
    {
        $begin = new \DateTime('now');
        $end = new \DateTime('2018-11-25 18:45:32');

        $sut = new DateRange();

        self::assertInstanceOf(DateRange::class, $sut->setBegin($begin));
        self::assertInstanceOf(DateRange::class, $sut->setEnd($end));

        self::assertInstanceOf(\DateTime::class, $sut->getBegin());
        self::assertInstanceOf(\DateTime::class, $sut->getEnd());

        self::assertEquals($begin->format('Y-m-d') . ' 00:00:00', $sut->getBegin()->format('Y-m-d H:i:s'));
        self::assertEquals('2018-11-25 23:59:59', $sut->getEnd()->format('Y-m-d H:i:s'));
    }

    public function testEquatableInterface(): void
    {
        self::assertTrue((new DateRange())->isEqualTo(new DateRange()));

        $sut = new DateRange();
        $sut->setBegin(new \DateTime('now'));
        $sut->setEnd(new \DateTime('+2 minutes'));

        $sut1 = new DateRange();
        $sut1->setBegin(new \DateTime('+2 minutes'));
        $sut1->setEnd(new \DateTime('now'));

        self::assertTrue($sut->isEqualTo($sut1));
        self::assertTrue($sut1->isEqualTo($sut));

        $sut = new DateRange(false);
        $sut->setBegin(new \DateTime('now'));
        $sut->setEnd(new \DateTime('+2 minutes'));

        $sut1 = new DateRange();
        $sut1->setBegin(new \DateTime('+2 minutes'));
        $sut1->setEnd(new \DateTime('now'));

        self::assertFalse($sut->isEqualTo($sut1));
        self::assertFalse($sut1->isEqualTo($sut));

        $sut = new DateRange();
        $sut->setBegin(new \DateTime('now'));

        $sut1 = new DateRange();
        $sut1->setBegin(new \DateTime('+1 day'));

        self::assertFalse($sut->isEqualTo($sut1));
        self::assertFalse($sut1->isEqualTo($sut));

        $sut = new DateRange();
        $sut->setBegin(new \DateTime('now'));

        $sut1 = new DateRange();
        $sut1->setEnd(new \DateTime('now'));

        self::assertFalse($sut->isEqualTo($sut1));
        self::assertFalse($sut1->isEqualTo($sut));

        $sut = new DateRange();
        $sut->setBegin(new \DateTime('now'));

        $sut1 = new DateRange();
        $sut1->setBegin(new \DateTime('now'));

        self::assertTrue($sut->isEqualTo($sut1));
        self::assertTrue($sut1->isEqualTo($sut));

        $sut = new DateRange();
        $sut->setEnd(new \DateTime('now'));

        $sut1 = new DateRange();
        $sut1->setEnd(new \DateTime('now'));

        self::assertTrue($sut->isEqualTo($sut1));
        self::assertTrue($sut1->isEqualTo($sut));
    }
}
