<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Timesheet;
use App\Validator\Constraints\TimesheetNegativeDuration;
use App\Validator\Constraints\TimesheetNegativeDurationValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<TimesheetNegativeDurationValidator>
 */
#[CoversClass(TimesheetNegativeDuration::class)]
#[CoversClass(TimesheetNegativeDurationValidator::class)]
class TimesheetNegativeDurationValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetNegativeDurationValidator
    {
        return new TimesheetNegativeDurationValidator();
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetNegativeDuration(['message' => 'Duration cannot be negative.']));
    }

    public function testNegativeDurationIsNotAllowed(): void
    {
        $begin = new \DateTime();
        $timesheet = new Timesheet();
        $timesheet->setBegin(clone $begin);
        $timesheet->setEnd(clone $begin);
        $timesheet->setBreak(3600);

        $this->validator->validate($timesheet, new TimesheetNegativeDuration(['message' => 'Duration cannot be negative.']));

        $this->buildViolation('Duration cannot be negative.')
            ->atPath('property.path.duration')
            ->setCode(TimesheetNegativeDuration::NEGATIVE_DURATION_ERROR)
            ->assertRaised();
    }

    public function testZeroDurationIsAllowed(): void
    {
        $begin = new \DateTime();
        $timesheet = new Timesheet();
        $timesheet->setBegin(clone $begin);
        $timesheet->setEnd(clone $begin);

        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate($timesheet, new TimesheetNegativeDuration(['message' => 'Duration cannot be negative.']));

        $this->assertNoViolation();
    }

    public function testDoesNotTriggerOnRunningTimesheet(): void
    {
        $begin = new \DateTime();
        $timesheet = new Timesheet();
        $timesheet->setBegin(clone $begin);
        $timesheet->setBreak(3600);

        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate($timesheet, new TimesheetNegativeDuration(['message' => 'Duration cannot be negative.']));

        $this->assertNoViolation();
    }
}
