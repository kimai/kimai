<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\DateFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateFormatter::class)]
class DateFormatterTest extends TestCase
{
    public function testGetFormat(): void
    {
        $formatter = new DateFormatter();
        self::assertEquals('yyyy-mm-dd', $formatter->getFormat());
    }

    public function testFormatValueReturnsFormattedDateForDateTime(): void
    {
        $formatter = new DateFormatter();
        $date = new \DateTime('2023-10-01');
        $result = $formatter->formatValue($date);
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
    }

    public function testFormatValueReturnsNullForNullValue(): void
    {
        $formatter = new DateFormatter();
        $result = $formatter->formatValue(null);
        self::assertNull($result);
    }

    public function testFormatValueConvertsIntoGivenTimezone(): void
    {
        $formatter = new DateFormatter(new \DateTimeZone('America/New_York'));
        $date = new \DateTime('2026-08-21 01:00:00', new \DateTimeZone('Europe/Berlin'));
        $result = $formatter->formatValue($date);
        self::assertInstanceOf(\DateTimeInterface::class, $result);
        self::assertEquals('2026-08-20 19:00:00', $result->format('Y-m-d H:i:s'));
        self::assertEquals('America/New_York', $result->getTimezone()->getName());

        // the original object must stay untouched
        self::assertEquals('Europe/Berlin', $date->getTimezone()->getName());
    }

    public function testFormatValueThrowsExceptionForNonDateTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only DateTimeInterface can be formatted');

        $formatter = new DateFormatter();
        $formatter->formatValue('not a date');
    }
}
