<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\TextFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextFormatter::class)]
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

    public function testFormatValueKeepsStringWithMaxLength(): void
    {
        $formatter = new TextFormatter(false);
        $value = str_repeat('a', TextFormatter::MAX_CHARACTERS_PER_CELL);
        self::assertSame($value, $formatter->formatValue($value));
    }

    public function testFormatValueTruncatesStringExceedingMaxLength(): void
    {
        $formatter = new TextFormatter(false);
        $value = str_repeat('a', TextFormatter::MAX_CHARACTERS_PER_CELL + 10);
        $result = $formatter->formatValue($value);
        self::assertIsString($result);
        self::assertSame(TextFormatter::MAX_CHARACTERS_PER_CELL, mb_strlen($result));
        self::assertSame(str_repeat('a', TextFormatter::MAX_CHARACTERS_PER_CELL), $result);
    }

    public function testFormatValueTruncatesMultibyteStringByCharacters(): void
    {
        $formatter = new TextFormatter(false);
        $value = str_repeat('ä', TextFormatter::MAX_CHARACTERS_PER_CELL + 1);
        $result = $formatter->formatValue($value);
        self::assertIsString($result);
        self::assertSame(TextFormatter::MAX_CHARACTERS_PER_CELL, mb_strlen($result));
        self::assertSame(str_repeat('ä', TextFormatter::MAX_CHARACTERS_PER_CELL), $result);
    }

    public function testFormatValueTruncatesAfterSanitizingDde(): void
    {
        $formatter = new TextFormatter(true);
        $value = '=' . str_repeat('a', TextFormatter::MAX_CHARACTERS_PER_CELL);
        $result = $formatter->formatValue($value);
        self::assertIsString($result);
        self::assertSame(TextFormatter::MAX_CHARACTERS_PER_CELL, mb_strlen($result));
        self::assertStringStartsWith('\' =aaa', $result);
    }

    public function testFormatValueReturnsNonStringValuesUnchanged(): void
    {
        $formatter = new TextFormatter(true);
        self::assertNull($formatter->formatValue(null));
        self::assertSame(123, $formatter->formatValue(123));
        self::assertSame(45.67, $formatter->formatValue(45.67));
        self::assertFalse($formatter->formatValue(false));
        self::assertSame(['foo', 'bar'], $formatter->formatValue(['foo', 'bar']));
    }
}
