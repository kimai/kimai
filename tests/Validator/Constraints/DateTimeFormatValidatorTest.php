<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\DateTimeFormat;
use App\Validator\Constraints\DateTimeFormatValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\DateTimeFormatValidator
 */
class DateTimeFormatValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new DateTimeFormatValidator();
    }

    public function getValidData()
    {
        return [
            ['10:00'],
            ['now'],
            ['2020-12-31 13:31:29'],
            ['monday this week 12:44'],
            [''], // empty is now
            [null], // null is now
        ];
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    /**
     * @dataProvider getValidData
     * @param string $input
     */
    public function testConstraintWithValidData($input)
    {
        $constraint = new DateTimeFormat();
        $this->validator->validate($input, $constraint);
        $this->assertNoViolation();
    }

    public function getInvalidData()
    {
        return [
            ['13-13'],
            ['3127::00'],
            ['3127:00:'],
            [':3127:00'],
            ['::3127'],
        ];
    }

    /**
     * @dataProvider getInvalidData
     * @param mixed $input
     */
    public function testValidationError($input)
    {
        $constraint = new DateTimeFormat();

        $this->validator->validate($input, $constraint);

        $expectedFormat = \is_string($input) ? '"' . $input . '"' : $input;

        $this->buildViolation('The given value is not a valid datetime format.')
            ->setCode(DateTimeFormat::INVALID_FORMAT)
            ->assertRaised();
    }
}
