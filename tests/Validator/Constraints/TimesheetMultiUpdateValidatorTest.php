<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Form\MultiUpdate\TimesheetMultiUpdateDTO;
use App\Validator\Constraints\TimesheetMultiUpdate as TimesheetMultiUpdateConstraint;
use App\Validator\Constraints\TimesheetMultiUpdateValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetMultiUpdateValidator
 */
class TimesheetMultiUpdateValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator($isGranted = true)
    {
        return new TimesheetMultiUpdateValidator();
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testProjectMismatch()
    {
        $activity = new Activity();
        $project1 = new Project();
        $project2 = new Project();
        $activity->setProject($project1);

        $timesheet = new TimesheetMultiUpdateDTO();
        $timesheet
            ->setActivity($activity)
            ->setProject($project2)
        ;

        $this->validator->validate($timesheet, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
            ->atPath('property.path.project')
            ->setCode(TimesheetMultiUpdateConstraint::ACTIVITY_PROJECT_MISMATCH_ERROR)
            ->assertRaised();
    }

    public function testProjectWithoutActivity()
    {
        $timesheet = new TimesheetMultiUpdateDTO();
        $timesheet
            ->setProject(new Project())
        ;

        $this->validator->validate($timesheet, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('You need to choose an activity, if the project should be changed.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetMultiUpdateConstraint::MISSING_ACTIVITY_ERROR)
            ->assertRaised();
    }

    public function testActivityWithoutProject()
    {
        $timesheet = new TimesheetMultiUpdateDTO();
        $timesheet
            ->setActivity((new Activity())->setProject(new Project()))
        ;

        $this->validator->validate($timesheet, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('Missing project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetMultiUpdateConstraint::MISSING_PROJECT_ERROR)
            ->assertRaised();
    }

    public function testHourlyRateAndFixedRateInParallelAreNotAllowed()
    {
        $timesheet = new TimesheetMultiUpdateDTO();
        $timesheet
            ->setHourlyRate(10.12)
            ->setFixedRate(123.45)
        ;

        $this->validator->validate($timesheet, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('Cannot set hourly rate and fixed rate at the same time.')
            ->atPath('property.path.fixedRate')
            ->setCode(TimesheetMultiUpdateConstraint::HOURLY_RATE_FIXED_RATE)
            ->buildNextViolation('Cannot set hourly rate and fixed rate at the same time.')
            ->atPath('property.path.hourlyRate')
            ->setCode(TimesheetMultiUpdateConstraint::HOURLY_RATE_FIXED_RATE)
            ->assertRaised();
    }

    public function testDisabledValues()
    {
        $customer = new Customer();
        $customer->setVisible(false);
        $activity = new Activity();
        $activity->setVisible(false);
        $project = new Project();
        $project->setVisible(false);
        $project->setCustomer($customer);
        $activity->setProject($project);

        $timesheet = new TimesheetMultiUpdateDTO();
        $timesheet
            ->setActivity($activity)
            ->setProject($project)
        ;

        $this->validator->validate($timesheet, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('Cannot assign a disabled activity.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetMultiUpdateConstraint::DISABLED_ACTIVITY_ERROR)
            ->buildNextViolation('Cannot assign a disabled project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetMultiUpdateConstraint::DISABLED_PROJECT_ERROR)
            ->buildNextViolation('Cannot assign a disabled customer.')
            ->atPath('property.path.customer')
            ->setCode(TimesheetMultiUpdateConstraint::DISABLED_CUSTOMER_ERROR)
            ->assertRaised();
    }
}
