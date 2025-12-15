<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\RateCalculator;

use App\Timesheet\RateCalculator\ClassicRateCalculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassicRateCalculator::class)]
class ClassicRateCalculatorTest extends TestCase
{
    #[DataProvider('provideRates')]
    public function testCalculateRate(float $hourlyRate, int $seconds, float $expected): void
    {
        $sut = new ClassicRateCalculator();

        $result = $sut->calculateRate($hourlyRate, $seconds);

        $this->assertEquals($expected, $result);
    }

    public static function provideRates(): array
    {
        return [
            'zero duration' => [100.0, 0, 0.0],
            'full hour' => [100.0, 3600, 100.0],
            'half hour' => [100.0, 1800, 50.0],
            'one minute with rounding' => [123.4567, 60, 2.0576],
            'one second tiny amount' => [1.23, 1, 0.0003],
        ];
    }

    public function testRoundDurationKeepsOriginalSeconds(): void
    {
        $sut = new ClassicRateCalculator();

        $this->assertSame(0, $sut->roundDuration(0));
        $this->assertSame(59, $sut->roundDuration(59));
        $this->assertSame(3601, $sut->roundDuration(3601));
    }

    public function testDecimalSettings(): void
    {
        $sut = new ClassicRateCalculator();

        $this->assertSame(4, $sut->getQuantityDecimals());
        $this->assertSame(4, $sut->getUnitAmountDecimals());
        $this->assertSame(2, $sut->getAmountDecimals());
    }
}
