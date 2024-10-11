<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\API\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\API\NotFoundException
 */
class NotFoundExceptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $sut = new NotFoundException();
        self::assertEquals('Not found', $sut->getMessage());
        self::assertEquals(404, $sut->getCode());
    }
}
