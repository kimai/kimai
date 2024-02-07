<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Activity;
use App\Entity\Timesheet;
use App\Validator\Constraints\QuickEntryTimesheet;
use App\Validator\Constraints\QuickEntryTimesheetValidator;
use App\Validator\Constraints\TimesheetBasic;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\QuickEntryTimesheet
 * @covers \App\Validator\Constraints\QuickEntryTimesheetValidator
 * @extends ConstraintValidatorTestCase<QuickEntryTimesheetValidator>
 */
class QuickEntryTimesheetValidatorTest extends ConstraintValidatorTestCase
{
    protected function createConstraint(): QuickEntryTimesheet
    {
        return new QuickEntryTimesheet();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new QuickEntryTimesheetValidator([new TimesheetBasic()]);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Activity(), $this->createConstraint());
    }

    public function testNotTriggersOnEmptyDurationAndNewTimesheet(): void
    {
        $timesheet = new Timesheet();
        $timesheet->setDuration(null);

        $this->validator->validate($timesheet, $this->createConstraint());

        $this->assertNoViolation();
    }
}
