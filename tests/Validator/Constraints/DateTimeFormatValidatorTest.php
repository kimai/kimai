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
 * @covers \App\Validator\Constraints\DateTimeFormat
 * @covers \App\Validator\Constraints\DateTimeFormatValidator
 * @extends ConstraintValidatorTestCase<DateTimeFormatValidator>
 */
class DateTimeFormatValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DateTimeFormatValidator
    {
        return new DateTimeFormatValidator();
    }

    public static function getValidData()
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

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    /**
     * @dataProvider getValidData
     */
    public function testConstraintWithValidData(?string $input): void
    {
        $constraint = new DateTimeFormat();
        $this->validator->validate($input, $constraint);
        $this->assertNoViolation();
    }

    public static function getInvalidData()
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
     */
    public function testValidationError(?string $input): void
    {
        $constraint = new DateTimeFormat();

        $this->validator->validate($input, $constraint);

        $this->buildViolation('This value is not a valid datetime.')
            ->setCode(DateTimeFormat::INVALID_FORMAT)
            ->assertRaised();
    }
}
