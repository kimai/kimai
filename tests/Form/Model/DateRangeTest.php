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
    public function testDefaultValues()
    {
        $sut = new DateRange();
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());
    }

    public function testSetterAndGetter()
    {
        $begin = new \DateTime('now');
        $end = new \DateTime('2018-11-25 18:45:32');

        $sut = new DateRange();

        self::assertInstanceOf(DateRange::class, $sut->setBegin($begin));
        self::assertInstanceOf(DateRange::class, $sut->setEnd($end));

        self::assertEquals($begin->format('Y-m-d') . ' 00:00:00', $sut->getBegin()->format('Y-m-d H:i:s'));
        self::assertEquals('2018-11-25 23:59:59', $sut->getEnd()->format('Y-m-d H:i:s'));
    }
}
