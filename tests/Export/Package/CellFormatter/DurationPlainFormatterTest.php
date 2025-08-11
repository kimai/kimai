<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\DurationPlainFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DurationPlainFormatter::class)]
class DurationPlainFormatterTest extends TestCase
{
    public function testFormatValueReturnsFormattedDurationQuiteLong(): void
    {
        $formatter = new DurationPlainFormatter(true);
        $result = $formatter->formatValue(701213);
        self::assertEquals('194:46:53', $result);

        $formatter = new DurationPlainFormatter(false);
        $result = $formatter->formatValue(701213);
        self::assertEquals('194:46', $result);
    }

    public function testFormatValueReturnsFormattedDurationForNumericValue(): void
    {
        $formatter = new DurationPlainFormatter(true);
        $result = $formatter->formatValue(8246);
        self::assertEquals('2:17:26', $result);
    }

    public function testFormatValueReturnsZeroForNonNumericValue(): void
    {
        $formatter = new DurationPlainFormatter(true);
        $result = $formatter->formatValue('not a number');
        self::assertEquals('0:00:00', $result);

        $formatter = new DurationPlainFormatter(false);
        $result = $formatter->formatValue('not a number');
        self::assertEquals('0:00', $result);
    }

    public function testFormatValueReturnsFormattedDurationForFloatValue(): void
    {
        $formatter = new DurationPlainFormatter(true);
        $result = $formatter->formatValue(44513.5);
        self::assertEquals('12:21:53', $result);
    }

    public function testFormatValueReturnsZeroForNullValue(): void
    {
        $formatter = new DurationPlainFormatter(true);
        $result = $formatter->formatValue(null);
        self::assertEquals('0:00:00', $result);
    }

    public function testFormatValueReturnsFormattedDurationForNegativeValue(): void
    {
        $formatter = new DurationPlainFormatter(true);
        $result = $formatter->formatValue(-3600);
        self::assertEquals('-1:00:00', $result);
    }
}
