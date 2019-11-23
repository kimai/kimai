<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Importer;

use App\Importer\InvalidFieldsException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Importer\InvalidFieldsException
 */
class InvalidFieldsExceptionTest extends TestCase
{
    public function testException()
    {
        $sut = new InvalidFieldsException(['test', 'foo']);
        self::assertEquals(['test', 'foo'], $sut->getFields());
        self::assertEquals('Missing fields: test, foo', $sut->getMessage());
    }
}
