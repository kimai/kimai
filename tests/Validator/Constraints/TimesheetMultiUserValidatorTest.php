<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Form\Model\MultiUserTimesheet;
use App\Validator\Constraints\TimesheetMultiUser;
use App\Validator\Constraints\TimesheetMultiUserValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<TimesheetMultiUserValidator>
 */
#[CoversClass(TimesheetMultiUser::class)]
#[CoversClass(TimesheetMultiUserValidator::class)]
class TimesheetMultiUserValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetMultiUserValidator
    {
        return new TimesheetMultiUserValidator();
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testEmptyTimesheet(): void
    {
        $timesheet = new MultiUserTimesheet();

        $this->validator->validate($timesheet, new TimesheetMultiUser(['message' => 'myMessage']));

        $this->buildViolation('You must select at least one user or team.')
            ->atPath('property.path.users')
            ->setCode(TimesheetMultiUser::MISSING_USER_OR_TEAM)
            ->buildNextViolation('You must select at least one user or team.')
            ->atPath('property.path.teams')
            ->setCode(TimesheetMultiUser::MISSING_USER_OR_TEAM)
            ->assertRaised();
    }
}
