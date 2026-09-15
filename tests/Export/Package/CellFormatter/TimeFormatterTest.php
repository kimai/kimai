<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\TimeFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimeFormatter::class)]
class TimeFormatterTest extends TestCase
{
    public function testFormatValueReturnsFormattedTimeForDateTimeInterface(): void
    {
        $formatter = new TimeFormatter();
        $dateTime = new \DateTime('2023-10-10 14:30:00');
        $result = $formatter->formatValue($dateTime);
        self::assertEquals('14:30', $result);
    }

    public function testFormatValueReturnsNullForNullValue(): void
    {
        $formatter = new TimeFormatter();
        $result = $formatter->formatValue(null);
        self::assertNull($result);
    }

    public function testFormatValueConvertsIntoGivenTimezone(): void
    {
        $formatter = new TimeFormatter(new \DateTimeZone('America/New_York'));
        $date = new \DateTime('2026-08-21 01:00:00', new \DateTimeZone('Europe/Berlin'));
        $result = $formatter->formatValue($date);
        self::assertEquals('19:00', $result);

        // the original object must stay untouched
        self::assertEquals('Europe/Berlin', $date->getTimezone()->getName());
    }

    public function testFormatValueIsUnchangedWhenGivenTimezoneMatchesTheRecordedTimezone(): void
    {
        $withoutTimezone = new TimeFormatter();
        $withMatchingTimezone = new TimeFormatter(new \DateTimeZone('Europe/Berlin'));

        $date = new \DateTime('2026-10-25 03:30:00', new \DateTimeZone('Europe/Berlin'));

        self::assertEquals($withoutTimezone->formatValue($date), $withMatchingTimezone->formatValue($date));
        self::assertEquals('03:30', $withMatchingTimezone->formatValue($date));
    }

    public function testFormatValueThrowsExceptionForNonDateTimeInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only DateTimeInterface can be formatted');

        $formatter = new TimeFormatter();
        $formatter->formatValue('not a DateTime');
    }
}
