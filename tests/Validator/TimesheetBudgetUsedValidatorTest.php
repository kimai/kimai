<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator;

use App\Configuration\SystemConfiguration;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\ActivityStatistic;
use App\Model\CustomerStatistic;
use App\Model\ProjectStatistic;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\RateService;
use App\Validator\Constraints\TimesheetBudgetUsedConstraint;
use App\Validator\TimesheetBudgetUsedValidator;
use DateTime;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetBudgetUsedConstraint
 * @covers \App\Validator\TimesheetBudgetUsedValidator
 */
class TimesheetBudgetUsedValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(bool $isAllowed = false, ?ActivityStatistic $activityStatistic = null, ?ProjectStatistic $projectStatistic = null, ?CustomerStatistic $customerStatistic = null)
    {
        $configuration = $this->createMock(SystemConfiguration::class);
        $configuration->method('isTimesheetAllowOverbookingBudget')->willReturn($isAllowed);

        $customerRepository = $this->createMock(CustomerRepository::class);
        $customerStatistic = $customerStatistic ?? new CustomerStatistic();
        $customerRepository->method('getCustomerStatistics')->willReturn($customerStatistic);

        $projectRepository = $this->createMock(ProjectRepository::class);
        $projectStatistic = $projectStatistic ?? new ProjectStatistic();
        $projectRepository->method('getProjectStatistics')->willReturn($projectStatistic);

        $activityRepository = $this->createMock(ActivityRepository::class);
        $activityStatistic = $activityStatistic ?? new ActivityStatistic();
        $activityRepository->method('getActivityStatistics')->willReturn($activityStatistic);

        $timesheetRepository = $this->createMock(TimesheetRepository::class);
        $rateService = new RateService([], $timesheetRepository);

        return new TimesheetBudgetUsedValidator($configuration, $customerRepository, $projectRepository, $activityRepository, $timesheetRepository, $rateService);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testTargetIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new TimesheetBudgetUsedConstraint());
    }

    public function testWithMissingEnd()
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime());

        $this->validator->validate($timesheet, new TimesheetBudgetUsedConstraint());
        $this->assertNoViolation();
    }

    public function testWithMissingUser()
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime());
        $timesheet->setEnd(new DateTime());

        $this->validator->validate($timesheet, new TimesheetBudgetUsedConstraint());
        $this->assertNoViolation();
    }

    public function testWithMissingProject()
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime());
        $timesheet->setEnd(new DateTime());
        $timesheet->setUser(new User());

        $this->validator->validate($timesheet, new TimesheetBudgetUsedConstraint());
        $this->assertNoViolation();
    }

    public function testWithoutBudget()
    {
        $project = new Project();
        $project->setCustomer(new Customer());

        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime());
        $timesheet->setEnd(new DateTime());
        $timesheet->setUser(new User());
        $timesheet->setProject($project);

        $this->validator->validate($timesheet, new TimesheetBudgetUsedConstraint());
        $this->assertNoViolation();
    }

    public function testWithAllowedOverbooking()
    {
        $this->validator = $this->createValidator(true);
        $this->validator->initialize($this->context);

        $activity = new Activity();
        $activity->setTimeBudget(3600);

        $begin = new DateTime();
        $end = clone $begin;
        $end->modify('+3601 seconds');

        $project = new Project();
        $project->setCustomer(new Customer());

        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);
        $timesheet->setUser(new User());
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->validator->validate($timesheet, new TimesheetBudgetUsedConstraint());
        $this->assertNoViolation();
    }

    public function getViolationTestData()
    {
        return [
            [1230, null, null, null, null, null, 3600, null, null, null, null, null, '00:20', '00:39', '01:00', 'activity', '+3600 seconds'],
        ];
    }

    /**
     * @dataProvider getViolationTestData
     */
    public function testWithActivityTimeBudget(
        ?int $activityDuration,
        ?int $activityRate,
        ?int $projectDuration,
        ?int $projectRate,
        ?int $customerDuration,
        ?int $customerRate,
        ?int $activityTimeBudget,
        ?int $activityBudget,
        ?int $projectTimeBudget,
        ?int $projectBudget,
        ?int $customerTimeBudget,
        ?int $customerBudget,
        string $used,
        string $free,
        string $budget,
        string $path,
        string $duration
    ) {
        $activityStatistic = new ActivityStatistic();
        if ($activityDuration !== null) {
            $activityStatistic->setRecordDuration($activityDuration);
        }
        if ($activityRate !== null) {
            $activityStatistic->setRecordRate($activityRate);
        }

        $projectStatistic = new ProjectStatistic();
        if ($projectDuration !== null) {
            $projectStatistic->setRecordDuration($projectDuration);
        }
        if ($projectRate !== null) {
            $projectStatistic->setRecordRate($projectRate);
        }

        $customerStatistic = new CustomerStatistic();
        if ($customerDuration !== null) {
            $customerStatistic->setRecordDuration($customerDuration);
        }
        if ($customerRate !== null) {
            $customerStatistic->setRecordRate($customerRate);
        }

        $this->validator = $this->createValidator(false, $activityStatistic, $projectStatistic, $customerStatistic);
        $this->validator->initialize($this->context);

        $activity = new Activity();
        if ($activityTimeBudget !== null) {
            $activity->setTimeBudget($activityTimeBudget);
        }
        if ($activityBudget !== null) {
            $activity->setBudget($activityBudget);
        }

        $customer = new Customer();
        if ($customerTimeBudget !== null) {
            $customer->setTimeBudget($customerTimeBudget);
        }
        if ($customerBudget !== null) {
            $customer->setBudget($customerBudget);
        }

        $project = new Project();
        if ($projectTimeBudget !== null) {
            $project->setTimeBudget($projectTimeBudget);
        }
        if ($projectBudget !== null) {
            $project->setBudget($projectBudget);
        }
        $project->setCustomer($customer);

        $begin = new DateTime();
        $end = clone $begin;
        $end->modify($duration);

        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);
        $timesheet->setUser(new User());
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->validator->validate($timesheet, new TimesheetBudgetUsedConstraint());

        $this->buildViolation('The budget is completely used.')
            ->atPath('property.path.' . $path)
            ->setParameters([
                '%used%' => $used,
                '%budget%' => $budget,
                '%free%' => $free
            ])
            ->assertRaised();
    }

    // FIXME add tests!!!
}
