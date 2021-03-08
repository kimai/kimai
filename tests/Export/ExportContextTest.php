<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Export\ExportContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\ExportContext
 */
class ExportContextTest extends TestCase
{
    public function testEmptyObject()
    {
        $sut = new ExportContext();

        self::assertIsArray($sut->getOptions());
        self::assertEmpty($sut->getOptions());
        self::assertNull($sut->getOption('unknown'));
    }

    public function testSetterAndGetter()
    {
        $sut = new ExportContext();

        self::assertNull($sut->getOption('unknown'));
        $sut->setOption('unknown', 'foo');
        self::assertEquals('foo', $sut->getOption('unknown'));
    }
}
