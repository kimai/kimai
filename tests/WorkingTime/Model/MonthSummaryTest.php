<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Model;

use App\WorkingTime\Model\MonthSummary;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Model\MonthSummary
 */
class MonthSummaryTest extends TestCase
{
    public function testDefaults(): void
    {
        $sut = new MonthSummary(new \DateTimeImmutable());
        self::assertEquals(0, $sut->getExpectedTime());
        self::assertEquals(0, $sut->getActualTime());
    }

    public function testSetter(): void
    {
        $sut = new MonthSummary(new \DateTimeImmutable());
        $sut->setExpectedTime(98765);
        $sut->setActualTime(123456);
        self::assertEquals(98765, $sut->getExpectedTime());
        self::assertEquals(123456, $sut->getActualTime());
    }
}
