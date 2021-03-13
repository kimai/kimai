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
use App\Timesheet\Rate;
use App\Timesheet\RateService;
use App\Timesheet\RateServiceInterface;
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
    protected function createValidator(bool $isAllowed = false, ?ActivityStatistic $activityStatistic = null, ?ProjectStatistic $projectStatistic = null, ?CustomerStatistic $customerStatistic = null, ?array $rawData = null, ?Rate $rate = null)
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
        if (null !== $rawData) {
            $timesheetRepository->method('getRawData')->willReturn($rawData);
        }

        if ($rate !== null) {
            $rateService = $this->createMock(RateServiceInterface::class);
            $rateService->method('calculate')->willReturn($rate);
        } else {
            $rateService = new RateService([], $timesheetRepository);
        }

        return new TimesheetBudgetUsedValidator($configuration, $customerRepository, $projectRepository, $activityRepository, $timesheetRepository, $rateService);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testConstraintWithPreExistingViolation()
    {
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
        $this->context->addViolation('FOOOOOOOOO');

        $this->validator->validate(new Timesheet(), new TimesheetBudgetUsedConstraint());
        $this->buildViolation('FOOOOOOOOO')->assertRaised();
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
            // activity: violations
            'a_a' => [1230, null, null, null, null, null,       3600, null, null, null, null, null,     '00:20', '00:39', '01:00', 'activity',          '+3600 seconds'],
            'a_b' => [null, 1001.0, null, null, null, null,     null, 1000.0, null, null, null, null,   '€1,001.00', '€0.00', '€1,000.00', 'activity',  '+3600 seconds'],

            // activity: no violations
            'a_c' => [1230, null, null, null, null, null,       null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],
            'a_d' => [null, 1001.0, null, null, null, null,     null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],
            'a_e' => [1230, 1001.0, null, null, null, null,     null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],

            //          previously logged                         available budgets                       expected violation                              duration            entry currently in database
            'a_f' => [1320, null, null, null, null, null,       3600, null, null, null, null, null,     '00:22', '00:38', '01:00', 'activity',          '+3600 seconds',    ['rate' => 1.0, 'duration' => 1000]],
            'a_h1' => [7200, null, null, null, null, null,       7200, null, null, null, null, null,     '02:00', '00:00', '02:00', 'activity',          '+3601 seconds',    ['rate' => 1.0, 'duration' => 3600]],
            'a_h' => [3601, null, null, null, null, null,       3600, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds',    ['rate' => 1.0, 'duration' => 3601]],
            'a_g' => [null, 1002.0, null, null, null, null,     null, 1000.0, null, null, null, null,   '1,002.00', '0.00', '1,000.00', 'activity',     '+3600 seconds',    ['rate' => 1.0, 'duration' => 1010]],
            'a_g1' => [null, 1002.0, null, null, null, null,     null, 1000.0, null, null, null, null,   null, null, null, null,                         '+3600 seconds',    ['rate' => 2.0, 'duration' => 0]],
            // nothing changed => no violation
            'a_x1' => [3600, 1000.0, null, null, null, null,     3600, 1000.0, null, null, null, null,   null, null, null, null,                         '+3600 seconds',    ['rate' => 1000.0, 'duration' => 3600], new Rate(1000.0, 0.00)],

            // project: violations
            'p_j' => [null, null, 1230, null, null, null,       null, null, 3600, null, null, null,     '00:20', '00:39', '01:00', 'project',           '+3600 seconds'],
            'p_k' => [null, null, null, 1001.0, null, null,     null, null, null, 1000.0, null, null,   '€1,001.00', '€0.00', '€1,000.00', 'project',   '+3600 seconds'],

            //          previously logged                         available budgets                       expected violation                              duration            entry currently in database
            'p_f' => [null, null, 1320, null, null, null,       null, null, 3600, null, null, null,     '00:22', '00:38', '01:00', 'project',           '+3600 seconds',    ['rate' => 1.0, 'duration' => 1000]],
            'p_h1' => [null, null, 7200, null, null, null,       null, null, 7200, null, null, null,     '02:00', '00:00', '02:00', 'project',           '+3601 seconds',    ['rate' => 1.0, 'duration' => 3600]],
            'p_h' => [null, null, 3601, null, null, null,       null, null, 3600, null, null, null,     null, null, null, null,                         '+3600 seconds',    ['rate' => 1.0, 'duration' => 3601]],
            'p_g' => [null, null, null, 1002.0, null, null,     null, null, null, 1000.0, null, null,   '1,002.00', '0.00', '1,000.00', 'project',      '+3600 seconds',    ['rate' => 1.0, 'duration' => 1010]],
            'p_g1' => [null, null, null, 1002.0, null, null,     null, null, null, 1000.0, null, null,   null, null, null, null,                         '+3600 seconds',    ['rate' => 2.0, 'duration' => 0]],

            // project: no violations
            'p_n' => [null, null, 1230, null, null, null,       null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],
            'p_o' => [null, null, null, 1001.0, null, null,     null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],
            'p_p' => [null, null, 1230, 1001, null, null,       null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],

            'p_q' => [1230, null, 1230, null, null, null,       null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],
            'p_r' => [1230, 1001.0, 1230, null, null, null,     null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],
            'p_s' => [null, 1001.0, null, 1001.0, null, null,   null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],
            'p_t' => [null, 1001.0, 1230, 1001.0, null, null,   null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],
            'p_u' => [1230, 1001.0, 1230, 1001.0, null, null,   null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds'],

            // customer: violations
            'c_v' => [null, null, null, null, 1230, null,       null, null, null, null, 3600, null,     '00:20', '00:39', '01:00', 'customer',          '+3600 seconds'],
            'c_w' => [null, null, null, null, null, 1001.0,     null, null, null, null, null, 1000.0,   '€1,001.00', '€0.00', '€1,000.00', 'customer',  '+3600 seconds'],

            //          previously logged                         available budgets                       expected violation                              duration            entry currently in database
            'c_f' => [null, null, null, null, 1320, null,       null, null, null, null, 3600, null,     '00:22', '00:38', '01:00', 'customer',          '+3600 seconds',    ['rate' => 1.0, 'duration' => 1000]],
            'c_h1' => [null, null, null, null, 7200, null,       null, null, null, null, 7200, null,     '02:00', '00:00', '02:00', 'customer',          '+3601 seconds',    ['rate' => 1.0, 'duration' => 3600]],
            'c_h' => [null, null, null, null, 3601, null,       null, null, null, null, 3600, null,     null, null, null, null,                         '+3600 seconds',    ['rate' => 1.0, 'duration' => 3601]],
            'c_g' => [null, null, null, null, null, 1002.0,     null, null, null, null, null, 1000.0,   '1,002.00', '0.00', '1,000.00', 'customer',     '+3600 seconds',    ['rate' => 1.0, 'duration' => 1010]],
            'c_g1' => [null, null, null, null, null, 1002.0,     null, null, null, null, null, 1000.0,   null, null, null, null,                         '+3600 seconds',    ['rate' => 2.0, 'duration' => 0]],

            // customer: no violations
            'c_z' => [null, null, null, null, 1230, null,       null, null, null, null, null, null,     null, null, null, null, '+3600 seconds'],
            'c_1' => [null, null, null, null, null, 1001.0,     null, null, null, null, null, null,     null, null, null, null, '+3600 seconds'],
            'c_2' => [null, null, null, null, 1230, 1001.0,     null, null, null, null, null, null,     null, null, null, null, '+3600 seconds'],
            'c_3' => [1230, null, 1230, null, 1230, null,       null, null, null, null, null, null,     null, null, null, null, '+3600 seconds'],
            'c_4' => [1230, 1001.0, 1230, null, null, 1001.0,   null, null, null, null, null, null,     null, null, null, null, '+3600 seconds'],
            'c_5' => [null, 1001.0, null, 1001.0, 1230, 1001.0, null, null, null, null, null, null,     null, null, null, null, '+3600 seconds'],
            'c_6' => [null, 1001.0, 1230, 1001.0, 1230, null,   null, null, null, null, null, null,     null, null, null, null, '+3600 seconds'],
            'c_7' => [1230, 1001.0, 1230, 1001.0, null, 1001.0, null, null, null, null, null, null,     null, null, null, null, '+3600 seconds'],
        ];
    }

    /**
     * @dataProvider getViolationTestData
     */
    public function testWithActivityTimeBudget(
        ?int $activityDuration,
        ?float $activityRate,
        ?int $projectDuration,
        ?float $projectRate,
        ?int $customerDuration,
        ?float $customerRate,
        ?int $activityTimeBudget,
        ?float $activityBudget,
        ?int $projectTimeBudget,
        ?float $projectBudget,
        ?int $customerTimeBudget,
        ?float $customerBudget,
        ?string $used,
        ?string $free,
        ?string $budget,
        ?string $path,
        string $duration,
        array $rawData = [],
        ?Rate $rate = null
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

        $begin = new DateTime();
        $end = clone $begin;
        $end->modify($duration);

        if (!empty($rawData)) {
            if (!\array_key_exists('activity', $rawData)) {
                $rawData['activity'] = 1;
            }
            if (!\array_key_exists('project', $rawData)) {
                $rawData['project'] = 1;
            }
            if (!\array_key_exists('customer', $rawData)) {
                $rawData['customer'] = 1;
            }
            $activity = $this->createMock(Activity::class);
            $activity->method('getId')->willReturn($rawData['activity']);
            if ($activityTimeBudget !== null) {
                $activity->method('getTimeBudget')->willReturn($activityTimeBudget);
                $activity->method('hasTimeBudget')->willReturn(true);
            }
            if ($activityBudget !== null) {
                $activity->method('getBudget')->willReturn($activityBudget);
                $activity->method('hasBudget')->willReturn(true);
            }

            $customer = $this->createMock(Customer::class);
            $customer->method('getId')->willReturn($rawData['customer']);
            if ($customerTimeBudget !== null) {
                $customer->method('getTimeBudget')->willReturn($customerTimeBudget);
                $customer->method('hasTimeBudget')->willReturn(true);
            }
            if ($customerBudget !== null) {
                $customer->method('getBudget')->willReturn($customerBudget);
                $customer->method('hasBudget')->willReturn(true);
            }

            $project = $this->createMock(Project::class);
            $project->method('getId')->willReturn($rawData['project']);
            $project->method('getCustomer')->willReturn($customer);
            if ($projectTimeBudget !== null) {
                $project->method('getTimeBudget')->willReturn($projectTimeBudget);
                $project->method('hasTimeBudget')->willReturn(true);
            }
            if ($projectBudget !== null) {
                $project->method('getBudget')->willReturn($projectBudget);
                $project->method('hasBudget')->willReturn(true);
            }

            $timesheet = $this->createMock(Timesheet::class);
            $timesheet->method('getId')->willReturn(1);
            $timesheet->method('getRate')->willReturn($rawData['rate']);
            $timesheet->method('getBegin')->willReturn($begin);
            $timesheet->method('getEnd')->willReturn($end);
            $timesheet->method('getUser')->willReturn(new User());
            $timesheet->method('getProject')->willReturn($project);
            $timesheet->method('getActivity')->willReturn($activity);
        } else {
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

            $timesheet = new Timesheet();
            $timesheet->setBegin($begin);
            $timesheet->setEnd($end);
            $timesheet->setUser(new User());
            $timesheet->setProject($project);
            $timesheet->setActivity($activity);
        }

        $this->validator = $this->createValidator(false, $activityStatistic, $projectStatistic, $customerStatistic, $rawData, $rate);
        $this->validator->initialize($this->context);

        $this->validator->validate($timesheet, new TimesheetBudgetUsedConstraint());

        if (null === $used && null === $budget && null === $free && $path === null) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('The budget is completely used.')
                ->atPath('property.path.' . $path)
                ->setParameters([
                    '%used%' => $used,
                    '%budget%' => $budget,
                    '%free%' => $free
                ])
                ->assertRaised();
        }
    }
}
