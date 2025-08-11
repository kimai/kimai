<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\DurationDecimalFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DurationDecimalFormatter::class)]
class DurationDecimalFormatterTest extends TestCase
{
    public function testFormatValueReturnsFormattedDurationForNumericValue(): void
    {
        $formatter = new DurationDecimalFormatter();
        $result = $formatter->formatValue(7200);
        self::assertEquals(2.00, $result);
    }

    public function testFormatValueReturnsZeroForNonNumericValue(): void
    {
        $formatter = new DurationDecimalFormatter();
        $result = $formatter->formatValue('not a number');
        self::assertEquals(0.0, $result);
    }

    public function testFormatValueReturnsFormattedDurationForFloatValue(): void
    {
        $formatter = new DurationDecimalFormatter();
        $result = $formatter->formatValue(4500.5);
        self::assertEquals(1.25, $result);
    }

    public function testFormatValueReturnsFormattedDurationForStringValue(): void
    {
        $formatter = new DurationDecimalFormatter();
        $result = $formatter->formatValue('4500.5');
        self::assertEquals(1.25, $result);
    }

    public function testFormatValueReturnsZeroForNullValue(): void
    {
        $formatter = new DurationDecimalFormatter();
        $result = $formatter->formatValue(null);
        self::assertEquals(0.0, $result);
    }

    public function testFormatValueReturnsFormattedDurationForNegativeValue(): void
    {
        $formatter = new DurationDecimalFormatter();
        $result = $formatter->formatValue(-3600);
        self::assertEquals(-1.00, $result);
    }

    public function testFormatValueReturnsFormattedDurationForNegativeStringValue(): void
    {
        $formatter = new DurationDecimalFormatter();
        $result = $formatter->formatValue('-3600');
        self::assertEquals(-1.00, $result);
    }
}
