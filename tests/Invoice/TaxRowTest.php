<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Entity\Tax;
use App\Entity\TaxType;
use App\Invoice\TaxRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaxRow::class)]
class TaxRowTest extends TestCase
{
    public function testDefaults(): void
    {
        $tax = new Tax(TaxType::STANDARD, 19.0, 'vat', true, null);
        $sut = new TaxRow($tax);

        self::assertSame($tax, $sut->getTax());
        self::assertSame(0.0, $sut->getBasePrice());
        self::assertSame(0.0, $sut->getAmount());
    }

    /**
     * @return iterable<array{float, float, float}>
     */
    public static function getAmountData(): iterable
    {
        yield [19.0, 100.0, 19.0];
        yield [19.0, 2386.27, 453.39];
        yield [19.0, 39150.03, 7438.51];
        // 7438.405 - must be rounded "half up" and not "half even" (intl default)
        yield [19.0, 39149.50, 7438.41];
        yield [7.0, 0.07, 0.0];
        yield [7.0, 0.08, 0.01];
        yield [0.0, 1234.56, 0.0];
        yield [19.0, -100.50, -19.10];
        // floating point noise: PHP >= 8.4 round() would return the lower value
        yield [21.0, 9.50, 2.0];
        yield [21.0, 25.50, 5.36];
    }

    #[DataProvider('getAmountData')]
    public function testGetAmountIsRoundedToCurrencyPrecision(float $rate, float $base, float $expected): void
    {
        $sut = new TaxRow(new Tax(TaxType::STANDARD, $rate, 'vat', true, null), $base);

        self::assertSame($base, $sut->getBasePrice());
        self::assertSame($expected, $sut->getAmount());
    }

    public function testGetAmountIsRoundedToGivenCurrency(): void
    {
        $tax = new Tax(TaxType::STANDARD, 10.0, 'vat', true, null);

        self::assertSame(123.5, (new TaxRow($tax, 1235.0))->getAmount());
        self::assertSame(123.5, (new TaxRow($tax, 1235.0, 'EUR'))->getAmount());
        self::assertSame(124.0, (new TaxRow($tax, 1235.0, 'JPY'))->getAmount());
        self::assertSame(1.235, (new TaxRow($tax, 12.345, 'KWD'))->getAmount());
    }
}
