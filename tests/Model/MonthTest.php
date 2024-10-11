<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\Month;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\Month
 */
class MonthTest extends TestCase
{
    public function testDefaults(): void
    {
        $months = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        foreach($months as $key => $days) {
            $index = ++$key;
            $monthKey = ($index < 10) ? '0' . $index : $index;
            $date = new \DateTimeImmutable(\sprintf('2020-%s-25 13:00:00', $monthKey));
            $month = new Month($date);
            self::assertEquals(\sprintf('2020-%s-25', $monthKey), $month->getMonth()->format('Y-m-d'));
            self::assertCount($days, $month->getDays());
        }
    }
}
