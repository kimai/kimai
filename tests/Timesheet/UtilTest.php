<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Timesheet\Util;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\Util
 */
class UtilTest extends TestCase
{
    /**
     * @dataProvider getRateCalculationData
     */
    public function testCalculateRate(int|float $hourlyRate, int $duration, int|float $expectedRate): void
    {
        $this->assertEquals($expectedRate, Util::calculateRate($hourlyRate, $duration));
    }

    public function getRateCalculationData()
    {
        yield [0, 0, 0];
        yield [1, 100, 0.0278];
        yield [1, 900, 0.25];
        yield [1, 1800, 0.5];
        yield [10000, 1, 2.7778];
        yield [736, 123, 25.1467];
        yield [7360, 1234, 2522.8444];
        yield [7360.34, 1234, 2522.961];
        yield [7360.01, 1234, 2522.8479];
        yield [7360.99, 1234, 2523.1838];
    }

    public function testCalculateRateWithRounding(): void
    {
        $total = 0.00;
        $seconds = 0;
        $repeat = 130;

        for ($a = 0; $a < $repeat; $a++) {
            $inputs = [
                900,
                1600,
                4200,
                8763,
                3300,
                600,
                1300,
                1837,
                4217,
                5400,
                3283,
                600,
            ];

            foreach ($inputs as $i) {
                $seconds += $i;
                $total += Util::calculateRate(114.75, $i);
            }
        }

        self::assertEquals(36000 * $repeat, $seconds);
        self::assertEquals(1147.50 * $repeat, $total);
    }
}
