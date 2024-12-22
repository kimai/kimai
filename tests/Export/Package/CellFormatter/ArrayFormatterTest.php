<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\ArrayFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Package\CellFormatter\ArrayFormatter
 */
class ArrayFormatterTest extends TestCase
{
    public function testFormatValueReturnsCommaSeparatedStringForArray(): void
    {
        $formatter = new ArrayFormatter();
        $result = $formatter->formatValue(['one', 'two', 'three']);
        self::assertEquals('one, two, three', $result);
    }

    public function testFormatValueThrowsExceptionForNonArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only arrays are supported');

        $formatter = new ArrayFormatter();
        $formatter->formatValue('not an array');
    }

    public function testFormatValueReturnsEmptyStringForEmptyArray(): void
    {
        $formatter = new ArrayFormatter();
        $result = $formatter->formatValue([]);
        self::assertEquals('', $result);
    }
}
