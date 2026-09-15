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

    public function testFormatValueConvertsIntoGivenTimezone(): void
    {
        $formatter = new DateStringFormatter(new \DateTimeZone('America/New_York'));
        $date = new \DateTime('2026-08-21 01:00:00', new \DateTimeZone('Europe/Berlin'));
        $result = $formatter->formatValue($date);
        self::assertEquals('2026-08-20', $result);

        // the original object must stay untouched
        self::assertEquals('Europe/Berlin', $date->getTimezone()->getName());
    }

    public function testFormatValueIsUnchangedWhenGivenTimezoneMatchesTheRecordedTimezone(): void
    {
        $withoutTimezone = new DateStringFormatter();
        $withMatchingTimezone = new DateStringFormatter(new \DateTimeZone('Europe/Berlin'));

        // 2026-10-25 03:30 CEST is the last wall-clock instant before Europe/Berlin falls back to CET
        $date = new \DateTime('2026-10-25 03:30:00', new \DateTimeZone('Europe/Berlin'));

        self::assertEquals($withoutTimezone->formatValue($date), $withMatchingTimezone->formatValue($date));
        self::assertEquals('2026-10-25', $withMatchingTimezone->formatValue($date));
    }

    public function testFormatValueUsesTheOffsetInEffectAtThatInstantAcrossDst(): void
    {
        $formatter = new DateStringFormatter(new \DateTimeZone('Europe/Berlin'));

        // 2026-10-25 is the day Europe/Berlin switches from CEST (+02:00) to CET (+01:00)
        $beforeDst = new \DateTime('2026-10-25 01:30:00', new \DateTimeZone('UTC'));
        $afterDst = new \DateTime('2026-10-25 23:30:00', new \DateTimeZone('UTC'));

        self::assertEquals('2026-10-25', $formatter->formatValue($beforeDst));
        self::assertEquals('2026-10-26', $formatter->formatValue($afterDst));
    }

    public function testFormatValueThrowsExceptionForNonDateTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only DateTimeInterface can be formatted');

        $formatter = new DateStringFormatter();
        $formatter->formatValue('not a date');
    }
}
