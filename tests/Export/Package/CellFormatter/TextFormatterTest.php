<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\TextFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Package\CellFormatter\TextFormatter
 */
class TextFormatterTest extends TestCase
{
    public function testFormatValueReturnsSanitizedStringWhenSanitizeDdeIsTrue(): void
    {
        $formatter = new TextFormatter(true);
        $result = $formatter->formatValue('=cmd|\' /C calc\'!A0');
        self::assertEquals('\' =cmd|\' /C calc\'!A0', $result);
    }

    public function testFormatValueReturnsOriginalStringWhenSanitizeDdeIsFalse(): void
    {
        $formatter = new TextFormatter(false);
        $result = $formatter->formatValue('=cmd|\' /C calc\'!A0');
        self::assertEquals('=cmd|\' /C calc\'!A0', $result);
    }

    public function testFormatValueReturnsOriginalValueForNonStringWhenSanitizeDdeIsTrue(): void
    {
        $formatter = new TextFormatter(true);
        $result = $formatter->formatValue(123);
        self::assertEquals(123, $result);

        $result = $formatter->formatValue(45.67);
        self::assertEquals(45.67, $result);

        $result = $formatter->formatValue(true);
        self::assertTrue($result);
    }

    public function testFormatValueReturnsOriginalValueForNonStringWhenSanitizeDdeIsFalse(): void
    {
        $formatter = new TextFormatter(false);
        $result = $formatter->formatValue(123);
        self::assertEquals(123, $result);

        $result = $formatter->formatValue(45.67);
        self::assertEquals(45.67, $result);

        $result = $formatter->formatValue(true);
        self::assertTrue($result);
    }
}
