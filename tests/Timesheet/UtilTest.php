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
    public function testCalculateRate($hourlyRate, $duration, $expectedRate)
    {
        $this->assertEquals($expectedRate, Util::calculateRate($hourlyRate, $duration));
    }

    public function getRateCalculationData()
    {
        yield [0, 0, 0];
        yield [1, 100, 0.028];
        yield [1, 900, 0.25];
        yield [1, 1800, 0.5];
        yield [10000, 1, 2.778];
        yield [736, 123.45, 25.147];
        yield [736, 123, 25.147];
        yield [7360, 1234.99, 2522.844];
        yield [7360, 1234, 2522.844];
        yield [7360.34, 1234, 2522.961];
        yield [7360.01, 1234, 2522.848];
        yield [7360.99, 1234, 2523.184];

        yield [27.50, 900, 6.875];
        yield [27.50, 1800, 13.75];
        yield [27.50, 2700, 20.625];
        yield [27.50, 5400, 41.25];
        yield [27.50, 7200, 55];
        yield [27.50, 11700, 89.375];
        yield [27.50, 15300, 116.875];
    }
}
