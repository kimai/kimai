<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator;

use App\Validator\ValidationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

#[CoversClass(ValidationFailedException::class)]
class ValidationFailedExceptionTest extends TestCase
{
    public function testException(): void
    {
        $list = new ConstraintViolationList();
        $sut = new ValidationFailedException($list);
        self::assertEquals(400, $sut->getCode());
        self::assertEquals('Validation Failed', $sut->getMessage());
        self::assertSame($list, $sut->getViolations());
    }

    public function testGetViolationsAsString(): void
    {
        $list = new ConstraintViolationList();
        $sut = new ValidationFailedException($list);
        self::assertEquals('', $sut->getViolationsAsString());

        $list = new ConstraintViolationList([
            new ConstraintViolation('This value is not a valid URL.', null, [], null, 'avatar', 'foo'),
            new ConstraintViolation('This value should not be blank.', null, [], null, 'alias', null),
        ]);
        $sut = new ValidationFailedException($list);
        self::assertEquals('avatar: This value is not a valid URL., alias: This value should not be blank.', $sut->getViolationsAsString());
    }

    public function testConstruct(): void
    {
        $list = new ConstraintViolationList();
        $sut = new ValidationFailedException($list, 'Something went wrong');
        self::assertEquals(400, $sut->getCode());
        self::assertEquals('Something went wrong', $sut->getMessage());
        self::assertSame($list, $sut->getViolations());
    }
}
