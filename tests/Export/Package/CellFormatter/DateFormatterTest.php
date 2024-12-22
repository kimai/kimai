<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\DateFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Package\CellFormatter\DateFormatter
 */
class DateFormatterTest extends TestCase
{
    public function testFormatValueReturnsFormattedDateForDateTime(): void
    {
        $formatter = new DateFormatter();
        $date = new \DateTime('2023-10-01');
        $result = $formatter->formatValue($date);
        self::assertEquals('2023-10-01', $result);
    }

    public function testFormatValueReturnsNullForNullValue(): void
    {
        $formatter = new DateFormatter();
        $result = $formatter->formatValue(null);
        self::assertNull($result);
    }

    public function testFormatValueThrowsExceptionForNonDateTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only DateTimeInterface can be formatted');

        $formatter = new DateFormatter();
        $formatter->formatValue('not a date');
    }
}
