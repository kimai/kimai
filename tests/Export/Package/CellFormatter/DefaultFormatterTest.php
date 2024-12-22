<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package\CellFormatter;

use App\Export\Package\CellFormatter\DefaultFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Package\CellFormatter\DefaultFormatter
 */
class DefaultFormatterTest extends TestCase
{
    public function testFormatValueReturnsSameValueForScalar(): void
    {
        $formatter = new DefaultFormatter();
        $result = $formatter->formatValue('string');
        self::assertEquals('string', $result);

        $result = $formatter->formatValue(123);
        self::assertEquals(123, $result);

        $result = $formatter->formatValue(45.67);
        self::assertEquals(45.67, $result);

        $result = $formatter->formatValue(true);
        self::assertTrue($result);

        $result = $formatter->formatValue(null);
        self::assertNull($result);
    }

    public function testFormatValueThrowsExceptionForNonScalar(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar values are supported');

        $formatter = new DefaultFormatter();
        $formatter->formatValue([]);
    }

    public function testFormatValueThrowsExceptionForObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar values are supported');

        $formatter = new DefaultFormatter();
        $formatter->formatValue(new \stdClass());
    }
}
