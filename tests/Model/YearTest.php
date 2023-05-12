<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\Year;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\Year
 */
class YearTest extends TestCase
{
    public function testDefaults(): void
    {
        $date = new \DateTimeImmutable('2020-03-14 13:00:00');
        $sut = new Year($date);
        self::assertCount(12, $sut->getMonths());
        self::assertSame($date, $sut->getYear());
        $months = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        foreach($months as $key => $days) {
            $index = ++$key;
            $monthKey = ($index < 10) ? '0' . $index : $index;
            $month = $sut->getMonth(new \DateTimeImmutable(sprintf('2020-%s-13 13:00:00', $monthKey)));
            self::assertEquals(sprintf('2020-%s-01', $monthKey), $month->getMonth()->format('Y-m-d'));
            self::assertCount($days, $month->getDays());
        }
    }
}
