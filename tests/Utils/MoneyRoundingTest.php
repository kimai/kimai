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
