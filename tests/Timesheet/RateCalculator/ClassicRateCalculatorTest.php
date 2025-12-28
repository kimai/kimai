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

    #[DataProvider('getRateCalculationData')]
    public function testCalculateRates(float $hourlyRate, int $duration, float $expectedRate): void
    {
        $sut = new ClassicRateCalculator();
        self::assertEquals($expectedRate, $sut->calculateRate($hourlyRate, $duration));
    }

    /**
     * @return array<int, array<float, int, >>|\Generator
     */
    public static function getRateCalculationData()
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
        $sut = new ClassicRateCalculator();

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
                $total += $sut->calculateRate(114.75, $i);
            }
        }

        self::assertEquals(36000 * $repeat, $seconds);
        self::assertEquals(1147.50 * $repeat, $total);
    }

    public function testDecimalDuration(): void
    {
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
            7200,
        ];

        $sut = new ClassicRateCalculator();
        foreach ($inputs as $row) {
            self::assertEquals($row, $sut->roundDuration($row));
        }
    }
}
