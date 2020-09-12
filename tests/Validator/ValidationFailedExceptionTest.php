<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator;

use App\Validator\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @covers \App\Validator\ValidationFailedException
 */
class ValidationFailedExceptionTest extends TestCase
{
    public function testException()
    {
        $list = new ConstraintViolationList();
        $sut = new ValidationFailedException($list);
        self::assertEquals(400, $sut->getCode());
        self::assertEquals('Validation failed', $sut->getMessage());
        self::assertSame($list, $sut->getViolations());
    }

    public function testConstruct()
    {
        $list = new ConstraintViolationList();
        $sut = new ValidationFailedException($list, 'Something went wrong');
        self::assertEquals(400, $sut->getCode());
        self::assertEquals('Something went wrong', $sut->getMessage());
        self::assertSame($list, $sut->getViolations());
    }
}
