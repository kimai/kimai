<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Timesheet;
use App\Validator\Constraints\TimesheetAll;
use App\Validator\Constraints\TimesheetAllValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<TimesheetAllValidator>
 */
#[CoversClass(TimesheetAll::class)]
#[CoversClass(TimesheetAllValidator::class)]
class TimesheetAllValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetAllValidator
    {
        return $this->createMyValidator();
    }

    protected function createMyValidator(bool $isGranted = true): TimesheetAllValidator
    {
        return new TimesheetAllValidator([]);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetAll());
    }
}
