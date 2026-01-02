<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\DurationFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DurationFormatter::class)]
class DurationFormatterTest extends TestCase
{
    public function testGetFormat(): void
    {
        $formatter = new DurationFormatter('[hh]:mm:ss');
        self::assertEquals('[hh]:mm:ss', $formatter->getFormat());
    }

    public function testFormatValueReturnsFormattedDurationQuiteLong(): void
    {
        $formatter = new DurationFormatter('[hh]:mm:ss');
        $result = $formatter->formatValue(701213);
        self::assertInstanceOf(\DateInterval::class, $result);
        self::assertEquals('194:46:53', $result->format('%r%H:%I:%S'));
    }

    public function testFormatValueReturnsFormattedDurationForNumericValue(): void
    {
        $formatter = new DurationFormatter('[hh]:mm:ss');
        $result = $formatter->formatValue(7213);
        self::assertInstanceOf(\DateInterval::class, $result);
        self::assertEquals('02:00:13', $result->format('%r%H:%I:%S'));
    }

    public function testFormatValueReturnsZeroForNonNumericValue(): void
    {
        $formatter = new DurationFormatter('[hh]:mm:ss');
        $result = $formatter->formatValue('not a number');
        self::assertInstanceOf(\DateInterval::class, $result);
        self::assertEquals('00:00:00', $result->format('%r%H:%I:%S'));
    }

    public function testFormatValueReturnsFormattedDurationForFloatValue(): void
    {
        $formatter = new DurationFormatter('[hh]:mm:ss');
        $result = $formatter->formatValue(4521.5);
        self::assertInstanceOf(\DateInterval::class, $result);
        self::assertEquals('01:15:21', $result->format('%r%H:%I:%S'));
    }

    public function testFormatValueReturnsZeroForNullValue(): void
    {
        $formatter = new DurationFormatter('[hh]:mm:ss');
        $result = $formatter->formatValue(null);
        self::assertInstanceOf(\DateInterval::class, $result);
        self::assertEquals('00:00:00', $result->format('%r%H:%I:%S'));
    }

    public function testFormatValueReturnsFormattedDurationForNegativeValue(): void
    {
        $formatter = new DurationFormatter('[hh]:mm:ss');
        $result = $formatter->formatValue(-3600);
        self::assertInstanceOf(\DateInterval::class, $result);
        self::assertEquals('-01:00:00', $result->format('%r%H:%I:%S'));
    }
}
