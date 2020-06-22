<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Importer;

use App\Importer\UnknownUserException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Importer\UnknownUserException
 */
class UnknownUserExceptionTest extends TestCase
{
    public function testException()
    {
        $sut = new UnknownUserException('test');
        self::assertEquals('test', $sut->getUsername());
        self::assertEquals('Unknown user: test', $sut->getMessage());
    }
}
