<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\DateStringFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateStringFormatter::class)]
class DateStringFormatterTest extends TestCase
{
    public function testFormatValueReturnsFormattedDateForDateTime(): void
    {
        $formatter = new DateStringFormatter();
        $date = new \DateTime('2023-10-01 12:37');
        $result = $formatter->formatValue($date);
        $this->assertIsString($result);
        $this->assertEquals('2023-10-01', $result);
    }

    public function testFormatValueReturnsNullForNullValue(): void
    {
        $formatter = new DateStringFormatter();
        $result = $formatter->formatValue(null);
        self::assertNull($result);
    }

    public function testFormatValueThrowsExceptionForNonDateTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only DateTimeInterface can be formatted');

        $formatter = new DateStringFormatter();
        $formatter->formatValue('not a date');
    }
}
