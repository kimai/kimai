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
    public function testDefaultValues()
    {
        $sut = new Year('1999');
        $this->assertNull($sut->getMonth('01'));
        $this->assertEmpty($sut->getMonths());
        $this->assertIsArray($sut->getMonths());
        $this->assertEquals('1999', $sut->getYear());
    }

    public function testSetter()
    {
        $sut = new Year('1999');

        $sut->setMonth(new Month('01'));
        $sut->setMonth(new Month('02'));
        $sut->setMonth(new Month('03'));
        $this->assertEquals(3, \count($sut->getMonths()));

        $sut->setMonth(new Month('01'));

        $this->assertEquals(3, \count($sut->getMonths()));

        $this->assertInstanceOf(Month::class, $sut->getMonth(1));
        $this->assertInstanceOf(Month::class, $sut->getMonth(2));
        $this->assertInstanceOf(Month::class, $sut->getMonth(3));
        $this->assertNull($sut->getMonth(4));
    }
}
