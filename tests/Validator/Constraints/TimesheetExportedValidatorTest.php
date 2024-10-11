<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Validator\Constraints\TimesheetExported;
use App\Validator\Constraints\TimesheetExportedValidator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetExported
 * @covers \App\Validator\Constraints\TimesheetExportedValidator
 * @extends ConstraintValidatorTestCase<TimesheetExportedValidator>
 */
class TimesheetExportedValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetExportedValidator
    {
        return $this->createMyValidator(true);
    }

    protected function createMyValidator(bool $allowEdit): TimesheetExportedValidator
    {
        $auth = $this->createMock(Security::class);
        $auth->method('getUser')->willReturn(new User());
        $auth->method('isGranted')->willReturnCallback(
            function ($attributes, $subject = null) use ($allowEdit) {
                switch ($attributes) {
                    case 'edit_export':
                        return $allowEdit;
                }

                return false;
            }
        );

        return new TimesheetExportedValidator($auth);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetExported(['message' => 'myMessage']));
    }

    public function testTriggersOnMissingPermission(): void
    {
        $this->validator = $this->createMyValidator(false);
        $this->validator->initialize($this->context);

        $timesheet = $this->createMock(Timesheet::class);
        $timesheet->method('isExported')->willReturn(true);
        $timesheet->method('getId')->willReturn(1);

        $this->validator->validate($timesheet, new TimesheetExported());

        $this->buildViolation('This timesheet is already exported.')
            ->atPath('property.path.exported')
            ->setCode(TimesheetExported::TIMESHEET_EXPORTED)
            ->assertRaised();
    }

    public function testNotTriggersOnNewTimesheet(): void
    {
        $this->validator = $this->createMyValidator(false);
        $this->validator->initialize($this->context);

        $timesheet = $this->createMock(Timesheet::class);
        $timesheet->method('isExported')->willReturn(true);
        $timesheet->method('getId')->willReturn(null);

        $this->validator->validate($timesheet, new TimesheetExported());

        $this->assertNoViolation();
    }

    public function testDoesNotTriggerWithPermission(): void
    {
        $this->validator = $this->createMyValidator(true);
        $this->validator->initialize($this->context);

        $timesheet = new Timesheet();
        $timesheet->setExported(true);

        $this->validator->validate($timesheet, new TimesheetExported());

        $this->assertNoViolation();
    }

    public function testDoesNotTriggerIfNotExported(): void
    {
        $this->validator = $this->createMyValidator(false);
        $this->validator->initialize($this->context);

        $timesheet = new Timesheet();
        $timesheet->setExported(false);

        $this->validator->validate($timesheet, new TimesheetExported());

        $this->assertNoViolation();
    }

    public function testGetTargets(): void
    {
        $constraint = new TimesheetExported();
        self::assertEquals('class', $constraint->getTargets());
    }
}
