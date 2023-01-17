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
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Validator\Constraints\TimesheetBasic;
use App\Validator\Constraints\TimesheetBasicValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetBasic
 * @covers \App\Validator\Constraints\TimesheetBasicValidator
 * @extends ConstraintValidatorTestCase<TimesheetBasicValidator>
 */
class TimesheetBasicValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetBasicValidator
    {
        return $this->createMyValidator();
    }

    protected function createMyValidator(): TimesheetBasicValidator
    {
        $configuration = SystemConfigurationFactory::createStub(['timesheet' => ['rules' => ['require_activity' => true]]]);

        return new TimesheetBasicValidator($configuration);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetBasic(['message' => 'myMessage']));
    }

    public function testEmptyTimesheet()
    {
        $timesheet = new Timesheet();
        $this->validator->validate($timesheet, new TimesheetBasic(['message' => 'myMessage']));

        $this->buildViolation('You must submit a begin date.')
            ->atPath('property.path.begin_date')
            ->setCode(TimesheetBasic::MISSING_BEGIN_ERROR)
            ->buildNextViolation('An activity needs to be selected.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetBasic::MISSING_ACTIVITY_ERROR)
            ->buildNextViolation('A project needs to be selected.')
            ->atPath('property.path.project')
            ->setCode(TimesheetBasic::MISSING_PROJECT_ERROR)
            ->assertRaised();
    }

    public function testFutureBegin()
    {
        $begin = new \DateTime('+10 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $this->validator->validate($timesheet, new TimesheetBasic(['message' => 'myMessage']));

        $this
            ->buildViolation('An activity needs to be selected.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetBasic::MISSING_ACTIVITY_ERROR)
            ->buildNextViolation('A project needs to be selected.')
            ->atPath('property.path.project')
            ->setCode(TimesheetBasic::MISSING_PROJECT_ERROR)
            // The test context is not able to handle calls to validate() - see ConstraintValidatorTestCase::createContext()
            // therefor sub-constraints will not be executed :-(
            /*
            ->buildNextViolation('The begin date cannot be in the future.')
            ->atPath('property.path.begin_date')
            ->setCode(TimesheetFutureTimes::BEGIN_IN_FUTURE_ERROR)
            */
            ->assertRaised();
    }

    public function testEndBeforeBegin()
    {
        $end = new \DateTime('-10 hour');
        $begin = new \DateTime('-1 hour');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);

        $this->validator->validate($timesheet, new TimesheetBasic(['message' => 'myMessage']));

        $this->buildViolation('End date must not be earlier then start date.')
            ->atPath('property.path.end_date')
            ->setCode(TimesheetBasic::END_BEFORE_BEGIN_ERROR)
            ->buildNextViolation('An activity needs to be selected.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetBasic::MISSING_ACTIVITY_ERROR)
            ->buildNextViolation('A project needs to be selected.')
            ->atPath('property.path.project')
            ->setCode(TimesheetBasic::MISSING_PROJECT_ERROR)
            ->assertRaised();
    }

    public function testProjectMismatch()
    {
        $end = new \DateTime('-1 hour');
        $begin = new \DateTime('-10 hour');
        $activity = new Activity();
        $project1 = new Project();
        $project2 = new Project();
        $project2->setCustomer(new Customer('foo'));
        $activity->setProject($project1);

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin($begin)
            ->setEnd($end)
            ->setActivity($activity)
            ->setProject($project2)
        ;

        $this->validator->validate($timesheet, new TimesheetBasic(['message' => 'myMessage']));

        $this->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
            ->atPath('property.path.project')
            ->setCode(TimesheetBasic::ACTIVITY_PROJECT_MISMATCH_ERROR)
            ->assertRaised();
    }

    public function testDisabledValuesDuringStart()
    {
        $begin = new \DateTime('-10 hour');
        $customer = new Customer('foo');
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

        $this->validator->validate($timesheet, new TimesheetBasic(['message' => 'myMessage']));

        $this->buildViolation('Cannot start a disabled activity.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetBasic::DISABLED_ACTIVITY_ERROR)
            ->buildNextViolation('Cannot start a disabled project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetBasic::DISABLED_PROJECT_ERROR)
            ->buildNextViolation('Cannot start a disabled customer.')
            ->atPath('property.path.customer')
            ->setCode(TimesheetBasic::DISABLED_CUSTOMER_ERROR)
            ->assertRaised();
    }

    public function getProjectStartEndTestData()
    {
        yield [new \DateTime(), new \DateTime(), [
            ['begin_date', TimesheetBasic::PROJECT_NOT_STARTED, 'The project has not started at that time.'],
            ['end_date', TimesheetBasic::PROJECT_NOT_STARTED, 'The project has not started at that time.'],
        ]];

        yield [new \DateTime('-9 hour'), new \DateTime('-2 hour'), [
            ['begin_date', TimesheetBasic::PROJECT_NOT_STARTED, 'The project has not started at that time.'],
            ['end_date', TimesheetBasic::PROJECT_ALREADY_ENDED, 'The project is finished at that time.'],
        ]];

        yield [new \DateTime('-19 hour'), new \DateTime('-12 hour'), [
            ['begin_date', TimesheetBasic::PROJECT_ALREADY_ENDED, 'The project is finished at that time.'],
            ['end_date', TimesheetBasic::PROJECT_ALREADY_ENDED, 'The project is finished at that time.'],
        ]];

        yield [new \DateTime('-19 hour'), new \DateTime('-2 hour'), [
            ['end_date', TimesheetBasic::PROJECT_ALREADY_ENDED, 'The project is finished at that time.'],
        ]];

        yield [new \DateTime('-9 hour'), new \DateTime(), [
            ['begin_date', TimesheetBasic::PROJECT_NOT_STARTED, 'The project has not started at that time.'],
        ]];
    }

    /**
     * @dataProvider getProjectStartEndTestData
     */
    public function testEndBeforeWithProjectStartAndEnd(\DateTime $start, \DateTime $end, array $violations)
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime('-10 hour'));
        $timesheet->setEnd(new \DateTime('-1 hour'));

        $customer = new Customer('foo');
        $project = new Project();
        $project->setStart($start);
        $project->setEnd($end);
        $project->setCustomer($customer);

        $timesheet->setProject($project);
        $timesheet->setActivity(new Activity());

        $this->validator->validate($timesheet, new TimesheetBasic(['message' => 'myMessage']));

        $assertion = null;
        foreach ($violations as $violation) {
            if (null === $assertion) {
                $assertion = $this->buildViolation($violation[2])
                    ->atPath('property.path.' . $violation[0])
                    ->setCode($violation[1])
                ;
            } else {
                $assertion = $assertion->buildNextViolation($violation[2])
                    ->atPath('property.path.' . $violation[0])
                    ->setCode($violation[1])
                ;
            }
        }
        $assertion->assertRaised();
    }

    public function testGetTargets()
    {
        $constraint = new TimesheetBasic();
        self::assertEquals('class', $constraint->getTargets());
    }
}
