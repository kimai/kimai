<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Model;

use App\WorkingTime\Model\YearSummary;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Model\YearSummary
 */
class YearSummaryTest extends TestCase
{
    public function testDefaults(): void
    {
        $monthDate = new \DateTimeImmutable();
        $sut = new YearSummary($monthDate, 'Foo-Bar');
        self::assertEquals('Foo-Bar', $sut->getTitle());
        self::assertEquals(0, $sut->getExpectedTime());
        self::assertEquals(0, $sut->getActualTime());
    }

    public function testWithSummary(): void
    {
        $monthDate = new \DateTimeImmutable('2020-01-01');
        $sut = new YearSummary($monthDate, 'Foo-Bar');

        $monthSummary = $sut->getMonth(new \DateTimeImmutable('2020-04-27'));
        $monthSummary->setActualTime(35679);
        $monthSummary->setExpectedTime(135679);

        $monthSummary = $sut->getMonth(new \DateTimeImmutable('2020-07-27'));
        $monthSummary->setActualTime(95679);
        $monthSummary->setExpectedTime(235679);

        self::assertEquals(131358, $sut->getActualTime());
        self::assertEquals(371358, $sut->getExpectedTime());
    }
}
