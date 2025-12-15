<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\RateCalculator;

use App\Timesheet\RateCalculator\DecimalRateCalculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DecimalRateCalculator::class)]
class DecimalRateCalculatorTest extends TestCase
{
    #[DataProvider('provideRates')]
    public function testCalculateRate(float $hourlyRate, int $seconds, float $expected): void
    {
        $sut = new DecimalRateCalculator();

        $result = $sut->calculateRate($hourlyRate, $seconds);

        $this->assertEquals($expected, $result);
    }

    public static function provideRates(): array
    {
        return [
            'zero duration' => [100.0, 0, 0.0],
            'full hour' => [100.0, 3600, 100.0],
            'half hour' => [100.0, 1800, 50.0],
            'one minute with rounding' => [123.4567, 60, 2.47],
            'one second tiny amount' => [1.23, 1, 0],
        ];
    }

    public function testRoundDurationKeepsOriginalSeconds(): void
    {
        $sut = new DecimalRateCalculator();

        $this->assertSame(0, $sut->roundDuration(0));
        $this->assertSame(36, $sut->roundDuration(45));
        $this->assertSame(72, $sut->roundDuration(59));
        $this->assertSame(1224, $sut->roundDuration(1234));
        $this->assertSame(3600, $sut->roundDuration(3601));
    }
}
