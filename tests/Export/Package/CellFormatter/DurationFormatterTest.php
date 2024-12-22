<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\DurationFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Package\CellFormatter\DurationFormatter
 */
class DurationFormatterTest extends TestCase
{
    public function testFormatValueReturnsFormattedDurationForNumericValue(): void
    {
        $formatter = new DurationFormatter();
        $result = $formatter->formatValue(7200);
        self::assertEquals(2.00, $result);
    }

    public function testFormatValueReturnsZeroForNonNumericValue(): void
    {
        $formatter = new DurationFormatter();
        $result = $formatter->formatValue('not a number');
        self::assertEquals(0.0, $result);
    }

    public function testFormatValueReturnsFormattedDurationForFloatValue(): void
    {
        $formatter = new DurationFormatter();
        $result = $formatter->formatValue(4500.5);
        self::assertEquals(1.25, $result);
    }

    public function testFormatValueReturnsZeroForNullValue(): void
    {
        $formatter = new DurationFormatter();
        $result = $formatter->formatValue(null);
        self::assertEquals(0.0, $result);
    }

    public function testFormatValueReturnsFormattedDurationForNegativeValue(): void
    {
        $formatter = new DurationFormatter();
        $result = $formatter->formatValue(-3600);
        self::assertEquals(-1.00, $result);
    }
}
