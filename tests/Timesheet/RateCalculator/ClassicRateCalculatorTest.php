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

    /**
     * @return iterable<string, array{float, int, float}>
     */
    public static function provideBoundaryRates(): iterable
    {
        // exact 778.125 - must be kept with 4 decimals
        yield '103.75 x 7.5h' => [103.75, 27000, 778.125];
        yield '103.75 x 0.5h' => [103.75, 1800, 51.875];
        // exact 1.734966... - rounded to 4 decimals
        yield '67.89 x 92s' => [67.89, 92, 1.735];
        // exact 64.83495, float is 64.83494999999999 - PHP >= 8.4 round() would return 64.8349
        yield '67.89 x 3438s float noise' => [67.89, 3438, 64.835];
        yield '99.99 x 618s float noise' => [99.99, 618, 17.165];
    }

    #[DataProvider('provideBoundaryRates')]
    public function testCalculateRateWithBoundaryValues(float $hourlyRate, int $seconds, float $expected): void
    {
        $sut = new ClassicRateCalculator();

        self::assertSame($expected, $sut->calculateRate($hourlyRate, $seconds));
        self::assertSame($expected, $sut->calculateRate($hourlyRate, $seconds, 'EUR'));
    }

    public function testCalculateRateIgnoresCurrency(): void
    {
        $sut = new ClassicRateCalculator();

        self::assertSame(1234.5678, $sut->calculateRate(1234.5678, 3600));
        self::assertSame(1234.5678, $sut->calculateRate(1234.5678, 3600, 'EUR'));
        self::assertSame(1234.5678, $sut->calculateRate(1234.5678, 3600, 'JPY'));
        self::assertSame(778.125, $sut->calculateRate(103.75, 27000, 'EUR'));
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
