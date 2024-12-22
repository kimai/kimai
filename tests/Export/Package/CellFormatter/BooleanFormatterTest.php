<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\BooleanFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Package\CellFormatter\BooleanFormatter
 */
class BooleanFormatterTest extends TestCase
{
    public function testFormatValueReturnsFalseForNull(): void
    {
        $formatter = new BooleanFormatter();
        $result = $formatter->formatValue(null);
        self::assertFalse($result);
    }

    public function testFormatValueReturnsTrueForTrue(): void
    {
        $formatter = new BooleanFormatter();
        $result = $formatter->formatValue(true);
        self::assertTrue($result);
    }

    public function testFormatValueReturnsFalseForFalse(): void
    {
        $formatter = new BooleanFormatter();
        $result = $formatter->formatValue(false);
        self::assertFalse($result);
    }

    public function testFormatValueReturnsTrueForNonZeroNumber(): void
    {
        $formatter = new BooleanFormatter();
        $result = $formatter->formatValue(1);
        self::assertTrue($result);
    }

    public function testFormatValueReturnsFalseForZero(): void
    {
        $formatter = new BooleanFormatter();
        $result = $formatter->formatValue(0);
        self::assertFalse($result);
    }

    public function testFormatValueReturnsTrueForNonEmptyString(): void
    {
        $formatter = new BooleanFormatter();
        $result = $formatter->formatValue('non-empty');
        self::assertTrue($result);
    }

    public function testFormatValueReturnsFalseForEmptyString(): void
    {
        $formatter = new BooleanFormatter();
        $result = $formatter->formatValue('');
        self::assertFalse($result);
    }

    public function testFormatValueThrowsExceptionForNonScalar(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar values are supported');

        $formatter = new BooleanFormatter();
        $formatter->formatValue([]);
    }
}
