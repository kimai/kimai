<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\MoneyRounding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoneyRounding::class)]
class MoneyRoundingTest extends TestCase
{
    /**
     * @return iterable<array{float, ?string, float}>
     */
    public static function getRoundData(): iterable
    {
        yield [778.125, null, 778.13];
        yield [51.875, null, 51.88];
        yield [1.005, null, 1.01];
        yield [0.285, null, 0.29];
        yield [1.234, null, 1.23];
        yield [1.235, 'EUR', 1.24];
        yield [7438.405, 'EUR', 7438.41];
        // halves are rounded away from zero
        yield [-19.095, 'EUR', -19.10];
        yield [-1.235, null, -1.24];
        // no negative zero
        yield [-0.004, 'EUR', 0.0];
        // floating point inaccuracies
        yield [0.1 + 0.2, 'EUR', 0.3];
        // currency precision
        yield [1234.5, 'JPY', 1235.0];
        yield [1234.49, 'jpy', 1234.0];
        yield [1.2345, 'KWD', 1.235];
        yield [1.2345, 'CHF', 1.23];
        yield [1.235, '', 1.24];
        yield [1.235, 'UNKNOWN', 1.24];
        // floating point noise: PHP >= 8.4 round() would return the lower value
        yield [103.75 * 0.42, 'EUR', 43.58];        // 43.574999999999996
        yield [9.5 * 0.21, 'EUR', 2.0];             // 1.9949999999999999
        yield [25.5 * 0.21, 'EUR', 5.36];           // 5.3549999999999995
        yield [-(103.75 * 0.42), 'EUR', -43.58];
        // values close to a half, which must not be rounded up
        yield [67.89 * 92 / 3600, 'EUR', 1.73];      // 1.7349666...
        yield [1.7349999, 'EUR', 1.73];
        yield [43.5749, 'EUR', 43.57];
        // large and small amounts
        yield [1234567.125, 'EUR', 1234567.13];
        yield [12345678.124, 'EUR', 12345678.12];
        yield [0.005, 'EUR', 0.01];
        yield [0.00499, 'EUR', 0.0];
    }

    /**
     * @return iterable<array{float, int, float}>
     */
    public static function getRoundToDecimalsData(): iterable
    {
        yield [64.835, 4, 64.835];
        // 64.83494999999999 - ClassicRateCalculator for 67.89/h and 3438 seconds
        yield [67.89 * (3438 / 3600), 4, 64.835];
        yield [67.89 * (92 / 3600), 4, 1.735];
        yield [778.125, 4, 778.125];
        yield [778.125, 2, 778.13];
        yield [778.125, 0, 778.0];
        yield [778.5, 0, 779.0];
        yield [-778.5, 0, -779.0];
        yield [1.23456789, 6, 1.234568];
        yield [0.0, 2, 0.0];
        yield [-0.00001, 4, 0.0];
    }

    #[DataProvider('getRoundToDecimalsData')]
    public function testRoundToDecimals(float $amount, int $decimals, float $expected): void
    {
        self::assertSame($expected, MoneyRounding::roundToDecimals($amount, $decimals));
    }

    #[DataProvider('getRoundData')]
    public function testRound(float $amount, ?string $currency, float $expected): void
    {
        self::assertSame($expected, MoneyRounding::round($amount, $currency));
    }

    public function testNoNegativeZero(): void
    {
        self::assertSame('0', (string) MoneyRounding::round(-0.004));
    }

    public function testGetFractionDigits(): void
    {
        self::assertSame(2, MoneyRounding::getFractionDigits());
        self::assertSame(2, MoneyRounding::getFractionDigits(null));
        self::assertSame(2, MoneyRounding::getFractionDigits(''));
        self::assertSame(2, MoneyRounding::getFractionDigits('EUR'));
        self::assertSame(2, MoneyRounding::getFractionDigits('usd'));
        self::assertSame(0, MoneyRounding::getFractionDigits('JPY'));
        self::assertSame(3, MoneyRounding::getFractionDigits('KWD'));
        self::assertSame(2, MoneyRounding::getFractionDigits('UNKNOWN'));
    }
}
