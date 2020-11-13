<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\TimeFormat;
use App\Validator\Constraints\TimeFormatValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimeFormatValidator
 */
class TimeFormatValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new TimeFormatValidator();
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testWrongValueThrowsException()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "stdClass" given');

        $this->validator->validate(new \stdClass(), new TimeFormat());
    }

    /**
     * @dataProvider getValidTimes
     */
    public function testValidationSucceeds(?string $value)
    {
        $this->validator->validate($value, new TimeFormat());
        $this->assertNoViolation();
    }

    public function getValidTimes()
    {
        return [
            [''],
            [null],
            ['00:00'],
            ['00:01'],
            ['23:00'],
            ['23:10'],
            ['23:01'],
            ['23:59'],
        ];
    }

    /**
     * @dataProvider getInvalidTimes
     */
    public function testValidationProblem(?string $value)
    {
        $this->validator->validate($value, new TimeFormat());

        $this->buildViolation('The given value is not a valid time.')
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->setCode(TimeFormat::INVALID_FORMAT)
            ->assertRaised();
    }

    public function getInvalidTimes()
    {
        return [
            ['a'],
            ['1:00'],
            ['01:1'],
            ['00:60'],
            ['23:60'],
            ['23:1'],
            ['24:00'],
        ];
    }
}
