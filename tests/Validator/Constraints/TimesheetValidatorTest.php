<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Timesheet;
use App\Validator\Constraints\Timesheet as TimesheetConstraint;
use App\Validator\Constraints\TimesheetValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\Timesheet
 * @covers \App\Validator\Constraints\TimesheetValidator
 * @extends ConstraintValidatorTestCase<TimesheetValidator>
 */
class TimesheetValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetValidator
    {
        return $this->createMyValidator();
    }

    protected function createMyValidator(bool $isGranted = true): TimesheetValidator
    {
        return new TimesheetValidator([]);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetConstraint(['message' => 'myMessage']));
    }
}
