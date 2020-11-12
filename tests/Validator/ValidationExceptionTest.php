<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator;

use App\Validator\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Validator\ValidationException
 */
class ValidationExceptionTest extends TestCase
{
    public function testException()
    {
        $sut = new ValidationException();
        self::assertEquals(400, $sut->getCode());
        self::assertEquals('Validation failed', $sut->getMessage());
    }

    public function testConstruct()
    {
        $sut = new ValidationException('Something went wrong');
        self::assertEquals(400, $sut->getCode());
        self::assertEquals('Something went wrong', $sut->getMessage());
    }
}
