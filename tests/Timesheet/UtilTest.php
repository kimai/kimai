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
        yield [1, 100, 0.03];
        yield [1, 900, 0.25];
        yield [1, 1800, 0.5];
        yield [10000, 1, 2.78];
        yield [736, 123.45, 25.15];
        yield [736, 123, 25.15];
        yield [7360, 1234.99, 2522.84];
        yield [7360, 1234, 2522.84];
        yield [7360.34, 1234, 2522.96];
        yield [7360.01, 1234, 2522.85];
        yield [7360.99, 1234, 2523.18];
    }
}
