<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Activity\ActivityStatisticService;
use App\Configuration\LocaleService;
use App\Customer\CustomerStatisticService;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\ActivityBudgetStatisticModel;
use App\Model\ActivityStatistic;
use App\Model\CustomerBudgetStatisticModel;
use App\Model\CustomerStatistic;
use App\Model\ProjectBudgetStatisticModel;
use App\Model\ProjectStatistic;
use App\Project\ProjectStatisticService;
use App\Repository\TimesheetRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Timesheet\Rate;
use App\Timesheet\RateService;
use App\Timesheet\RateServiceInterface;
use App\Validator\Constraints\TimesheetBudgetUsed;
use App\Validator\Constraints\TimesheetBudgetUsedValidator;
use DateTime;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetBudgetUsed
 * @covers \App\Validator\Constraints\TimesheetBudgetUsedValidator
 * @extends ConstraintValidatorTestCase<TimesheetBudgetUsedValidator>
 */
class TimesheetBudgetUsedValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @param array<mixed>|null $rawData
     */
    protected function createValidator(bool $isAllowed = false, ?ActivityBudgetStatisticModel $activityStatisticModel = null, ?ProjectBudgetStatisticModel $projectStatisticModel = null, ?CustomerBudgetStatisticModel $customerStatisticModel = null, ?array $rawData = null, ?Rate $rate = null): TimesheetBudgetUsedValidator
    {
        $configuration = SystemConfigurationFactory::createStub(['timesheet' => ['rules' => ['allow_overbooking_budget' => $isAllowed]]]);

        if ($customerStatisticModel === null) {
            $customerStatisticModel = new CustomerBudgetStatisticModel(new Customer('foo'));
            $customerStatistic = new CustomerStatistic();
            $customerStatisticModel->setStatisticTotal($customerStatistic);
            $customerStatisticModel->setStatistic($customerStatistic);
        }

        $customerRepository = $this->createMock(CustomerStatisticService::class);
        $customerRepository->method('getBudgetStatisticModel')->willReturn($customerStatisticModel);

        if ($projectStatisticModel === null) {
            $projectStatisticModel = new ProjectBudgetStatisticModel(new Project());
            $projectStatistic = new ProjectStatistic();
            $projectStatisticModel->setStatisticTotal($projectStatistic);
            $projectStatisticModel->setStatistic($projectStatistic);
        }

        $projectRepository = $this->createMock(ProjectStatisticService::class);
        $projectRepository->method('getBudgetStatisticModel')->willReturn($projectStatisticModel);

        if ($activityStatisticModel === null) {
            $activityStatisticModel = new ActivityBudgetStatisticModel(new Activity());
            $activityStatistic = new ActivityStatistic();
            $activityStatisticModel->setStatisticTotal($activityStatistic);
            $activityStatisticModel->setStatistic($activityStatistic);
        }

        $activityRepository = $this->createMock(ActivityStatisticService::class);
        $activityRepository->method('getBudgetStatisticModel')->willReturn($activityStatisticModel);

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

        $auth = $this->createMock(AuthorizationCheckerInterface::class);

        $localeService = new LocaleService([]);

        return new TimesheetBudgetUsedValidator($configuration, $customerRepository, $projectRepository, $activityRepository, $timesheetRepository, $rateService, $auth, $localeService);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testConstraintWithPreExistingViolation(): void
    {
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
        $this->context->addViolation('FOOOOOOOOO');

        $this->validator->validate(new Timesheet(), new TimesheetBudgetUsed());
        $this->buildViolation('FOOOOOOOOO')->assertRaised();
    }

    public function testTargetIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new TimesheetBudgetUsed());
    }

    public function testWithMissingEnd(): void
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime());

        $this->validator->validate($timesheet, new TimesheetBudgetUsed());
        $this->assertNoViolation();
    }

    public function testWithMissingUser(): void
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime());
        $timesheet->setEnd(new DateTime());

        $this->validator->validate($timesheet, new TimesheetBudgetUsed());
        $this->assertNoViolation();
    }

    public function testWithMissingProject(): void
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime());
        $timesheet->setEnd(new DateTime());
        $timesheet->setUser(new User());

        $this->validator->validate($timesheet, new TimesheetBudgetUsed());
        $this->assertNoViolation();
    }

    public function testWithoutBudget(): void
    {
        $project = new Project();
        $project->setCustomer(new Customer('foo'));

        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime());
        $timesheet->setEnd(new DateTime());
        $timesheet->setUser(new User());
        $timesheet->setProject($project);

        $this->validator->validate($timesheet, new TimesheetBudgetUsed());
        $this->assertNoViolation();
    }

    public function testWithAllowedOverbooking(): void
    {
        $this->validator = $this->createValidator(true);
        $this->validator->initialize($this->context);

        $activity = new Activity();
        $activity->setTimeBudget(3600);

        $begin = new DateTime();
        $end = clone $begin;
        $end->modify('+3601 seconds');

        $project = new Project();
        $project->setCustomer(new Customer('foo'));

        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setEnd($end);
        $timesheet->setUser(new User());
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->validator->validate($timesheet, new TimesheetBudgetUsed());
        $this->assertNoViolation();
    }

    public static function getViolationTestData()
    {
        return [
            // activity: violations ----------------------------------------------------------------------
            //        previously logged                         available budgets                                           expected violation                              duration            entry currently in database
            'a_a' => [1230, null, null, null, null, null,       null, 3600, null, null, null, null, null, null, null,       '0:20', '0:39', '1:00', 'activity',          '+3600 seconds'],
            'a_b' => [null, 1001.0, null, null, null, null,     null, null, 1000.0, null, null, null, null, null, null,     '€1,001.00', '€0.00', '€1,000.00', 'activity',  '+3600 seconds'],

            // activity: no violations
            'a_c' => [1230, null, null, null, null, null,       null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],
            'a_d' => [null, 1001.0, null, null, null, null,     null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],
            'a_e' => [1230, 1001.0, null, null, null, null,     null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],

            //        previously logged                         available budgets                                           expected violation                              duration            entry currently in database
            'a_f1' => [1320, null, null, null, null, null,      null, 3600, null, null, null, null, null, null, null,       '0:22', '0:38', '1:00', 'activity',          '+3600 seconds',    ['rate' => 1.0, 'duration' => 1000]],
            'a_h1' => [7200, null, null, null, null, null,      null, 7200, null, null, null, null, null, null, null,       '2:00', '0:00', '2:00', 'activity',          '+3601 seconds',    ['rate' => 1.0, 'duration' => 3600]],
            'a_h2' => [3601, null, null, null, null, null,      null, 3600, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds',    ['rate' => 1.0, 'duration' => 3601]],
            'a_g0' => [null, 1002.0, null, null, null, null,    null, null, 1000.0, null, null, null, null, null, null,     '€1,002.00', '€0.00', '€1,000.00', 'activity',     '+3600 seconds',    ['rate' => 1.0, 'duration' => 1010]],
            'a_g1' => [null, 1002.0, null, null, null, null,    null, null, 1000.0, null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds',    ['rate' => 2.0, 'duration' => 0]],
            // nothing changed => no violation
            'a_x1' => [3600, 1000.0, null, null, null, null,    null, 3600, 1000.0, null, null, null, null, null, null,     null, null, null, null,                         '+3600 seconds',    ['rate' => 1000.0, 'duration' => 3600], new Rate(1000.0, 0.00)],
            // date changed => violation
            'a_x2' => [3600, 1000.0, null, null, null, null,    'month', 3600, 1000.0, null, null, null, null, null, null,  '€1,000.00', '€0.00', '€1,000.00', 'activity',     '+3600 seconds',    ['rate' => 999.0, 'duration' => 3599, 'begin' => new DateTime('2021-03-17 16:15:00'), 'end' => new DateTime('2021-03-17 17:15:01'), 'billable' => true], new Rate(1000.0, 0.00)],
            // date changed but not violation was raised
            'a_x3' => [3600, 1000.0, null, null, null, null,    'month', 3600, 1000.0, null, null, null, null, null, null,  null, null, null, null,                         '+3600 seconds',    ['rate' => 999.0, 'duration' => 3599, 'begin' => new DateTime('2021-03-17 16:15:00'), 'end' => new DateTime('2021-03-17 17:15:01'), 'billable' => false], new Rate(1000.0, 0.00)],
            'a_x4' => [3600, 1000.0, null, null, null, null,    'month', 3600, 1000.0, null, null, null, null, null, null,  null, null, null, null,                         '+3600 seconds',    ['rate' => 1000.0, 'duration' => 3600, 'begin' => new DateTime('2021-03-17 16:15:00'), 'end' => new DateTime('2021-03-17 17:15:01'), 'billable' => true], new Rate(1000.0, 0.00)],
            'a_x5' => [3600, 1000.0, null, null, null, null,    'month', 3600, 1000.0, null, null, null, null, null, null,  null, null, null, null,                         '+3600 seconds',    ['rate' => 1000.0, 'duration' => 3600, 'begin' => new DateTime('2021-03-17 16:15:00'), 'end' => new DateTime('2021-03-17 17:15:01'), 'billable' => false], new Rate(1000.0, 0.00)],

            // project: violations ----------------------------------------------------------------------
            'p_j' => [null, null, 1230, null, null, null,       null, null, null, null, 3600, null, null, null, null,       '0:20', '0:39', '1:00', 'project',           '+3600 seconds'],
            'p_k' => [null, null, null, 1001.0, null, null,     null, null, null, null, null, 1000.0, null, null, null,     '€1,001.00', '€0.00', '€1,000.00', 'project',   '+3600 seconds'],

            //        previously logged                         available budgets                                           expected violation                              duration            entry currently in database
            'p_f1' => [null, null, 1320, null, null, null,      null, null, null, null, 3600, null, null, null, null,       '0:22', '0:38', '1:00', 'project',           '+3600 seconds',    ['rate' => 1.0, 'duration' => 1000]],
            'p_h1' => [null, null, 7200, null, null, null,      null, null, null, null, 7200, null, null, null, null,       '2:00', '0:00', '2:00', 'project',           '+3601 seconds',    ['rate' => 1.0, 'duration' => 3600]],
            'p_h2' => [null, null, 3601, null, null, null,      null, null, null, null, 3600, null, null, null, null,       null, null, null, null,                         '+3600 seconds',    ['rate' => 1.0, 'duration' => 3601]],
            'p_g0' => [null, null, null, 1002.0, null, null,    null, null, null, null, null, 1000.0, null, null, null,     '€1,002.00', '€0.00', '€1,000.00', 'project',      '+3600 seconds',    ['rate' => 1.0, 'duration' => 1010]],
            'p_g1' => [null, null, null, 1002.0, null, null,    null, null, null, null, null, 1000.0, null, null, null,     null, null, null, null,                         '+3600 seconds',    ['rate' => 2.0, 'duration' => 0]],

            // project: no violations
            'p_n' => [null, null, 1230, null, null, null,       null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],
            'p_o' => [null, null, null, 1001.0, null, null,     null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],
            'p_p' => [null, null, 1230, 1001, null, null,       null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],

            'p_q' => [1230, null, 1230, null, null, null,       null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],
            'p_r' => [1230, 1001.0, 1230, null, null, null,     null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],
            'p_s' => [null, 1001.0, null, 1001.0, null, null,   null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],
            'p_t' => [null, 1001.0, 1230, 1001.0, null, null,   null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],
            'p_u' => [1230, 1001.0, 1230, 1001.0, null, null,   null, null, null, null, null, null, null, null, null,       null, null, null, null,                         '+3600 seconds'],

            // customer: violations ----------------------------------------------------------------------
            'c_v' => [null, null, null, null, 1230, null,       null, null, null, null, null, null, null, 3600, null,       '0:20', '0:39', '1:00', 'customer',          '+3600 seconds'],
            'c_w' => [null, null, null, null, null, 1001.0,     null, null, null, null, null, null, null, null, 1000.0,     '€1,001.00', '€0.00', '€1,000.00', 'customer',  '+3600 seconds'],

            //        previously logged                         available budgets                                           expected violation                              duration            entry currently in database
            'c_f1' => [null, null, null, null, 1320, null,      null, null, null, null, null, null, null, 3600, null,       '0:22', '0:38', '1:00', 'customer',          '+3600 seconds',    ['rate' => 1.0, 'duration' => 1000]],
            'c_h1' => [null, null, null, null, 7200, null,      null, null, null, null, null, null, null, 7200, null,       '2:00', '0:00', '2:00', 'customer',          '+3601 seconds',    ['rate' => 1.0, 'duration' => 3600]],
            'c_h2' => [null, null, null, null, 3601, null,      null, null, null, null, null, null, null, 3600, null,       null, null, null, null,                         '+3600 seconds',    ['rate' => 1.0, 'duration' => 3601]],
            'c_g0' => [null, null, null, null, null, 1002.0,    null, null, null, null, null, null, null, null, 1000.0,     '€1,002.00', '€0.00', '€1,000.00', 'customer',     '+3600 seconds',    ['rate' => 1.0, 'duration' => 1010]],
            'c_g1' => [null, null, null, null, null, 1002.0,    null, null, null, null, null, null, null, null, 1000.0,     null, null, null, null,                         '+3600 seconds',    ['rate' => 2.0, 'duration' => 0]],

            // customer: no violations
            'c_z' => [null, null, null, null, 1230, null,       null, null, null, null, null, null, null, null, null,       null, null, null, null, '+3600 seconds'],
            'c_1' => [null, null, null, null, null, 1001.0,     null, null, null, null, null, null, null, null, null,       null, null, null, null, '+3600 seconds'],
            'c_2' => [null, null, null, null, 1230, 1001.0,     null, null, null, null, null, null, null, null, null,       null, null, null, null, '+3600 seconds'],
            'c_3' => [1230, null, 1230, null, 1230, null,       null, null, null, null, null, null, null, null, null,       null, null, null, null, '+3600 seconds'],
            'c_4' => [1230, 1001.0, 1230, null, null, 1001.0,   null, null, null, null, null, null, null, null, null,       null, null, null, null, '+3600 seconds'],
            'c_5' => [null, 1001.0, null, 1001.0, 1230, 1001.0, null, null, null, null, null, null, null, null, null,       null, null, null, null, '+3600 seconds'],
            'c_6' => [null, 1001.0, 1230, 1001.0, 1230, null,   null, null, null, null, null, null, null, null, null,       null, null, null, null, '+3600 seconds'],
            'c_7' => [1230, 1001.0, 1230, 1001.0, null, 1001.0, null, null, null, null, null, null, null, null, null,       null, null, null, null, '+3600 seconds'],
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
        ?string $activityBudgetType,
        ?int $activityTimeBudget,
        ?float $activityBudget,
        ?string $projectBudgetType,
        ?int $projectTimeBudget,
        ?float $projectBudget,
        ?string $customerBudgetType,
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
            $activityStatistic->setDuration($activityDuration);
            $activityStatistic->setDurationBillable($activityDuration);
        }
        if ($activityRate !== null) {
            $activityStatistic->setRate($activityRate);
            $activityStatistic->setRateBillable($activityRate);
        }

        $projectStatistic = new ProjectStatistic();
        if ($projectDuration !== null) {
            $projectStatistic->setDuration($projectDuration);
            $projectStatistic->setDurationBillable($projectDuration);
        }
        if ($projectRate !== null) {
            $projectStatistic->setRate($projectRate);
            $projectStatistic->setRateBillable($projectRate);
        }

        $customerStatistic = new CustomerStatistic();
        if ($customerDuration !== null) {
            $customerStatistic->setDuration($customerDuration);
            $customerStatistic->setDurationBillable($customerDuration);
        }
        if ($customerRate !== null) {
            $customerStatistic->setRate($customerRate);
            $customerStatistic->setRateBillable($customerRate);
        }

        $begin = new DateTime();
        $end = clone $begin;
        $end->modify($duration);

        $customer = null;
        $project = null;
        $activity = null;

        if (!empty($rawData)) {
            if (!\array_key_exists('activity', $rawData)) {
                $rawData['activity'] = 1;
            }
            if (!\array_key_exists('billable', $rawData)) {
                $rawData['billable'] = true;
            }
            if (!\array_key_exists('project', $rawData)) {
                $rawData['project'] = 1;
            }
            if (!\array_key_exists('customer', $rawData)) {
                $rawData['customer'] = 1;
            }
            if (!\array_key_exists('rate', $rawData)) {
                $rawData['rate'] = 0.00;
            }
            if (!\array_key_exists('duration', $rawData)) {
                $rawData['duration'] = 0;
            }
            if (!\array_key_exists('begin', $rawData)) {
                $rawData['begin'] = clone $begin;
            }
            if (!\array_key_exists('end', $rawData)) {
                $rawData['end'] = clone $end;
            }
            $activity = $this->createMock(Activity::class);
            $activity->method('getId')->willReturn($rawData['activity']);
            $activity->method('isMonthlyBudget')->willReturn(false);
            if ($activityBudgetType !== null) {
                $activity->method('getBudgetType')->willReturn($activityBudgetType);
                $activity->method('isMonthlyBudget')->willReturn($activityBudgetType === 'month');
            }
            if ($activityTimeBudget !== null) {
                $activity->method('getTimeBudget')->willReturn($activityTimeBudget);
                $activity->method('hasTimeBudget')->willReturn(true);
                $activity->method('hasBudgets')->willReturn(true);
            }
            if ($activityBudget !== null) {
                $activity->method('getBudget')->willReturn($activityBudget);
                $activity->method('hasBudget')->willReturn(true);
                $activity->method('hasBudgets')->willReturn(true);
            }

            $customer = $this->createMock(Customer::class);
            $customer->method('getCurrency')->willReturn('EUR');
            $customer->method('getId')->willReturn($rawData['customer']);
            $customer->method('isMonthlyBudget')->willReturn(false);
            if ($customerBudgetType !== null) {
                $customer->method('getBudgetType')->willReturn($customerBudgetType);
                $customer->method('isMonthlyBudget')->willReturn($customerBudgetType === 'month');
            }
            if ($customerTimeBudget !== null) {
                $customer->method('getTimeBudget')->willReturn($customerTimeBudget);
                $customer->method('hasTimeBudget')->willReturn(true);
                $customer->method('hasBudgets')->willReturn(true);
            }
            if ($customerBudget !== null) {
                $customer->method('getBudget')->willReturn($customerBudget);
                $customer->method('hasBudget')->willReturn(true);
                $customer->method('hasBudgets')->willReturn(true);
            }

            $project = $this->createMock(Project::class);
            $project->method('getId')->willReturn($rawData['project']);
            $project->method('getCustomer')->willReturn($customer);
            $project->method('isMonthlyBudget')->willReturn(false);
            if ($projectBudgetType !== null) {
                $project->method('getBudgetType')->willReturn($projectBudgetType);
                $project->method('isMonthlyBudget')->willReturn($projectBudgetType === 'month');
            }
            if ($projectTimeBudget !== null) {
                $project->method('getTimeBudget')->willReturn($projectTimeBudget);
                $project->method('hasTimeBudget')->willReturn(true);
                $project->method('hasBudgets')->willReturn(true);
            }
            if ($projectBudget !== null) {
                $project->method('getBudget')->willReturn($projectBudget);
                $project->method('hasBudget')->willReturn(true);
                $project->method('hasBudgets')->willReturn(true);
            }
            $timesheet = $this->createMock(Timesheet::class);
            $timesheet->method('getId')->willReturn(1);
            $timesheet->method('getRate')->willReturn($rawData['rate']);
            $timesheet->method('getBegin')->willReturn($begin);
            $timesheet->method('getEnd')->willReturn($end);
            $timesheet->method('getCalculatedDuration')->willReturn($end->getTimestamp() - $begin->getTimestamp());
            $timesheet->method('getDuration')->willReturn($end->getTimestamp() - $begin->getTimestamp());
            $timesheet->method('getUser')->willReturn(new User());
            $timesheet->method('getProject')->willReturn($project);
            $timesheet->method('getActivity')->willReturn($activity);
            $timesheet->method('isBillable')->willReturn($rawData['billable']);
        } else {
            $activity = new Activity();
            if ($activityTimeBudget !== null) {
                $activity->setTimeBudget($activityTimeBudget);
            }
            if ($activityBudget !== null) {
                $activity->setBudget($activityBudget);
            }

            $customer = new Customer('foo');
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

        $activityBudgetStatisticModel = new ActivityBudgetStatisticModel($activity);
        $activityBudgetStatisticModel->setStatistic($activityStatistic);
        $activityBudgetStatisticModel->setStatisticTotal($activityStatistic);

        $projectBudgetStatisticModel = new ProjectBudgetStatisticModel($project);
        $projectBudgetStatisticModel->setStatistic($projectStatistic);
        $projectBudgetStatisticModel->setStatisticTotal($projectStatistic);

        $customerBudgetStatisticModel = new CustomerBudgetStatisticModel($customer);
        $customerBudgetStatisticModel->setStatistic($customerStatistic);
        $customerBudgetStatisticModel->setStatisticTotal($customerStatistic);

        $this->validator = $this->createValidator(false, $activityBudgetStatisticModel, $projectBudgetStatisticModel, $customerBudgetStatisticModel, $rawData, $rate);
        $this->validator->initialize($this->context);

        $this->validator->validate($timesheet, new TimesheetBudgetUsed());

        if (null === $used && null === $budget && null === $free && $path === null) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('Sorry, the budget is used up.')
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
