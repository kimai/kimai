<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\TimeFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Package\CellFormatter\TimeFormatter
 */
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

    public function testFormatValueThrowsExceptionForNonDateTimeInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only DateTimeInterface can be formatted');

        $formatter = new TimeFormatter();
        $formatter->formatValue('not a DateTime');
    }
}
