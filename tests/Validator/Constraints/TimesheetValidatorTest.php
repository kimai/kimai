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
use App\Entity\Timesheet;
use App\Validator\Constraints\Timesheet as TimesheetConstraint;
use App\Validator\Constraints\TimesheetValidator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetValidator
 */
class TimesheetValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator($isGranted = true)
    {
        $options = [
            'allow_future_times' => false
        ];

        $authMock = $this->getMockBuilder(AuthorizationCheckerInterface::class)->getMock();
        $authMock->method('isGranted')->willReturn($isGranted);

        return new TimesheetValidator($authMock, $options, false);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testConstraintIsInvalid()
    {
        $this->validator->validate('foo', new NotBlank());
    }

    public function testEmptyTimesheet()
    {
        $timesheet = new Timesheet();
        $this->validator->validate($timesheet, new TimesheetConstraint(['message' => 'myMessage']));

        $this->buildViolation('You must submit a begin date.')
            ->atPath('property.path.begin')
            ->setCode(TimesheetConstraint::MISSING_BEGIN_ERROR)
            ->buildNextViolation('A timesheet must have an activity.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetConstraint::MISSING_ACTIVITY_ERROR)
            ->buildNextViolation('A timesheet must have a project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetConstraint::MISSING_PROJECT_ERROR)
            ->assertRaised();
    }

    public function testFutureBegin()
    {
        $begin = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $this->validator->validate($timesheet, new TimesheetConstraint(['message' => 'myMessage']));

        $this->buildViolation('The begin date cannot be in the future.')
            ->atPath('property.path.begin')
            ->setCode(TimesheetConstraint::BEGIN_IN_FUTURE_ERROR)
            ->buildNextViolation('A timesheet must have an activity.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetConstraint::MISSING_ACTIVITY_ERROR)
            ->buildNextViolation('A timesheet must have a project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetConstraint::MISSING_PROJECT_ERROR)
            ->assertRaised();
    }

    public function testRestartDisallowed()
    {
        $this->validator = $this->createValidator(false);
        $this->validator->initialize($this->context);

        $begin = new \DateTime('-10 hour');
        $customer = new Customer();
        $activity = new Activity();
        $project = new Project();
        $project->setCustomer($customer);
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin($begin)
            ->setActivity($activity)
            ->setProject($project)
        ;

        $this->validator->validate($timesheet, new TimesheetConstraint(['message' => 'myMessage']));

        $this->buildViolation('You are not allowed to start this timesheet record.')
            ->atPath('property.path.end')
            ->setCode(TimesheetConstraint::START_DISALLOWED)
            ->assertRaised();
    }

    public function testEndBeforeBegin()
    {
        $end = new \DateTime('-10 hour');
        $begin = new \DateTime('-1 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetConstraint(['message' => 'myMessage']));

        $this->buildViolation('End date must not be earlier then start date.')
            ->atPath('property.path.end')
            ->setCode(TimesheetConstraint::END_BEFORE_BEGIN_ERROR)
            ->buildNextViolation('A timesheet must have an activity.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetConstraint::MISSING_ACTIVITY_ERROR)
            ->buildNextViolation('A timesheet must have a project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetConstraint::MISSING_PROJECT_ERROR)
            ->assertRaised();
    }

    public function testProjectMismatch()
    {
        $end = new \DateTime('-1 hour');
        $begin = new \DateTime('-10 hour');
        $activity = new Activity();
        $project1 = new Project();
        $project2 = new Project();
        $activity->setProject($project1);

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin($begin)
            ->setEnd($end)
            ->setActivity($activity)
            ->setProject($project2)
        ;

        $this->validator->validate($timesheet, new TimesheetConstraint(['message' => 'myMessage']));

        $this->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
            ->atPath('property.path.project')
            ->setCode(TimesheetConstraint::ACTIVITY_PROJECT_MISMATCH_ERROR)
            ->assertRaised();
    }

    public function testDisabledValuesDuringStart()
    {
        $begin = new \DateTime('-10 hour');
        $customer = new Customer();
        $customer->setVisible(false);
        $activity = new Activity();
        $activity->setVisible(false);
        $project = new Project();
        $project->setVisible(false);
        $project->setCustomer($customer);
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin($begin)
            ->setActivity($activity)
            ->setProject($project)
        ;

        $this->validator->validate($timesheet, new TimesheetConstraint(['message' => 'myMessage']));

        $this->buildViolation('Cannot start a disabled activity.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetConstraint::DISABLED_ACTIVITY_ERROR)
            ->buildNextViolation('Cannot start a disabled project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetConstraint::DISABLED_PROJECT_ERROR)
            ->buildNextViolation('Cannot start a disabled customer.')
            ->atPath('property.path.customer')
            ->setCode(TimesheetConstraint::DISABLED_CUSTOMER_ERROR)
            ->assertRaised();
    }
}
