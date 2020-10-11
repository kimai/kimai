<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Importer;

use App\Importer\UnsupportedFormatException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Importer\UnsupportedFormatException
 */
class UnsupportedFormatExceptionTest extends TestCase
{
    public function testException()
    {
        $sut = new UnsupportedFormatException('test');
        self::assertEquals('test', $sut->getMessage());
        self::assertEquals(0, $sut->getCode());
    }
}
