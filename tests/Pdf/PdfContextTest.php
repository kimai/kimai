<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Pdf;

use App\Pdf\PdfContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Pdf\PdfContext
 */
class PdfContextTest extends TestCase
{
    public function testEmptyObject(): void
    {
        $sut = new PdfContext();

        self::assertIsArray($sut->getOptions());
        self::assertEmpty($sut->getOptions());
        self::assertNull($sut->getOption('unknown'));
    }

    public function testSetterAndGetter(): void
    {
        $sut = new PdfContext();

        self::assertNull($sut->getOption('unknown'));
        $sut->setOption('unknown', 'foo');
        self::assertEquals('foo', $sut->getOption('unknown'));
    }
}
