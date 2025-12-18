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

    #[DataProvider('getRateCalculationData')]
    public function testCalculateRates(float $hourlyRate, int $duration, float $expectedRate): void
    {
        $sut = new DecimalRateCalculator();
        self::assertEquals($expectedRate, $sut->calculateRate($hourlyRate, $duration));
    }

    /**
     * @return array<int, array<float, int, >>|\Generator
     */
    public static function getRateCalculationData()
    {
        yield [0.00, 0, 0.00];
        yield [10.00, 7260, 20.2];
        yield [1.00, 3600, 1.00];
        yield [1.00, 100, 0.03];
        yield [1.00, 900, 0.25];
        yield [1.00, 1800, 0.5];
        yield [10000.00, 60, 200.00];
        yield [736.00, 123, 22.08];
        yield [7360.00, 1234, 2502.4];
        yield [7360.34, 1234, 2502.52];
        yield [7360.01, 1234, 2502.4];
        yield [7360.99, 1234, 2502.74];
    }

    public function testCalculateRateWithRounding(): void
    {
        $total = 0.00;
        $seconds = 0;
        $repeat = 130;

        $inputs = [
            [900, 28.69, 0],
            [1600, 50.49, 0],
            [4200, 134.26, 0],
            [8763, 278.84, 0],
            [3300, 105.57, 0],
            [600, 19.51, 0],
            [1300, 41.31, 0],
            [1837, 58.52, 0],
            [4217, 134.26, 0],
            [5400, 172.13, 0],
            [3283, 104.42, 0],
            [600, 19.51, 0],
        ];

        $totalExpected = 0.00;
        $sut = new DecimalRateCalculator();

        for ($a = 0; $a < $repeat; $a++) {
            foreach ($inputs as $row) {
                [$duration, $rate] = $row;
                $seconds += $duration;
                $totalExpected += $rate;
                $tmp = $sut->calculateRate(114.75, $duration);
                self::assertEquals($rate, $tmp);
                $total += $tmp;
            }
        }

        self::assertEquals(36000 * $repeat, $seconds);
        self::assertEquals($totalExpected, $total);
        self::assertEqualsWithDelta(1147.51 * $repeat, $total, 0.00001);
        self::assertEqualsWithDelta(149176.3, $total, 0.00001);
    }

    public function testDecimalDuration(): void
    {
        $inputs = [
            [900, 900],
            [1600, 1584],
            [4200, 4212],
            [8763, 8748],
            [3300, 3312],
            [600, 612],
            [1300, 1296],
            [1837, 1836],
            [4217, 4212],
            [5400, 5400],
            [3283, 3276],
            [600, 612],
            [7200, 7200],
        ];

        $sut = new DecimalRateCalculator();
        foreach ($inputs as $row) {
            self::assertEquals($row[1], $sut->roundDuration($row[0]));
        }
    }
}
