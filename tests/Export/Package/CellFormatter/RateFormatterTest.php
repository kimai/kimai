<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\RateFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Package\CellFormatter\RateFormatter
 */
class RateFormatterTest extends TestCase
{
    public function testFormatValueReturnsFormattedFloatForNumericValue(): void
    {
        $formatter = new RateFormatter();
        $result = $formatter->formatValue(1234.5678);
        self::assertEquals(1234.57, $result);

        $result = $formatter->formatValue('1234.5678');
        self::assertEquals(1234.57, $result);
    }

    public function testFormatValueThrowsForNonNumericValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only numeric values be formatted');

        $formatter = new RateFormatter();
        $result = $formatter->formatValue('not a number');
        self::assertEquals(0.0, $result);
    }

    public function testFormatValueReturnsZeroForNullValue(): void
    {
        $formatter = new RateFormatter();
        $result = $formatter->formatValue(null);
        self::assertEquals(0.0, $result);
    }

    public function testFormatValueReturnsFormattedFloatForNegativeNumericValue(): void
    {
        $formatter = new RateFormatter();
        $result = $formatter->formatValue(-1234.5678);
        self::assertEquals(-1234.57, $result);
    }

    public function testFormatValueReturnsFormattedFloatForFloatWithTwoDecimalPlaces(): void
    {
        $formatter = new RateFormatter();
        $result = $formatter->formatValue(1234.56);
        self::assertEquals(1234.56, $result);
    }

    public function testFormatValueThrowsExceptionForNonNumericNonNullValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only numeric values be formatted');

        $formatter = new RateFormatter();
        $formatter->formatValue(new \stdClass());
    }

    public function testFormatValueThrowsExceptionFoArrayValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only numeric values be formatted');

        $formatter = new RateFormatter();
        $formatter->formatValue([]);
    }
}
