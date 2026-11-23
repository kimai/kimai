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
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\MultiUpdate\TimesheetMultiUpdateDTO;
use App\Security\RolePermissionManager;
use App\User\PermissionService;
use App\Validator\Constraints\TimesheetMultiUpdate as TimesheetMultiUpdateConstraint;
use App\Validator\Constraints\TimesheetMultiUpdateValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<TimesheetMultiUpdateValidator>
 */
#[CoversClass(TimesheetMultiUpdateConstraint::class)]
#[CoversClass(TimesheetMultiUpdateValidator::class)]
class TimesheetMultiUpdateValidatorTest extends ConstraintValidatorTestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        parent::setUp();
    }

    protected function createValidator(): TimesheetMultiUpdateValidator
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturnCallback(fn () => $this->user);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('getPermissions')->willReturn([]);
        $permissionManager = new RolePermissionManager($permissionService, [], []);

        return new TimesheetMultiUpdateValidator($security, $permissionManager);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testProjectMismatch(): void
    {
        $activity = new Activity();
        $project1 = new Project();
        $project2 = new Project();
        $activity->setProject($project1);

        $timesheet = new TimesheetMultiUpdateDTO();
        $timesheet->setActivity($activity);
        $timesheet->setProject($project2);

        $this->validator->validate($timesheet, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
            ->atPath('property.path.project')
            ->setCode(TimesheetMultiUpdateConstraint::ACTIVITY_PROJECT_MISMATCH_ERROR)
            ->assertRaised();
    }

    public function testProjectWithoutActivity(): void
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

    public function testActivityWithoutProject(): void
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

    public function testHourlyRateAndFixedRateInParallelAreNotAllowed(): void
    {
        $timesheet = new TimesheetMultiUpdateDTO();
        $timesheet->setHourlyRate(10.12);
        $timesheet->setFixedRate(123.45);

        $this->validator->validate($timesheet, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('Cannot set hourly rate and fixed rate at the same time.')
            ->atPath('property.path.fixedRate')
            ->setCode(TimesheetMultiUpdateConstraint::HOURLY_RATE_FIXED_RATE)
            ->buildNextViolation('Cannot set hourly rate and fixed rate at the same time.')
            ->atPath('property.path.hourlyRate')
            ->setCode(TimesheetMultiUpdateConstraint::HOURLY_RATE_FIXED_RATE)
            ->assertRaised();
    }

    public function testDisabledValues(): void
    {
        $customer = new Customer('foo');
        $customer->setVisible(false);
        $activity = new Activity();
        $activity->setVisible(false);
        $project = new Project();
        $project->setVisible(false);
        $project->setCustomer($customer);
        $activity->setProject($project);

        $timesheet = new TimesheetMultiUpdateDTO();
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);

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

    private function createLockedProjectDto(?string $lockedUntil, string $begin): TimesheetMultiUpdateDTO
    {
        $project = new Project();
        $project->setName('foo');
        $project->setCustomer(new Customer('bar'));
        if ($lockedUntil !== null) {
            $project->setLockedUntil(new \DateTimeImmutable($lockedUntil));
        }

        $activity = new Activity();
        $activity->setName('an activity');

        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime($begin));

        $dto = new TimesheetMultiUpdateDTO();
        $dto->setEntities([$timesheet]);
        $dto->setActivity($activity);
        $dto->setProject($project);

        return $dto;
    }

    public function testCannotAssignProjectWithLockedPeriod(): void
    {
        // the selected records would end up inside the locked period of the new project
        $dto = $this->createLockedProjectDto('2020-06-30 23:59:59', '2020-06-15 10:00:00');

        $this->validator->validate($dto, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('The project is locked for the selected times.')
            ->atPath('property.path.project')
            ->setCode(TimesheetMultiUpdateConstraint::LOCKED_PROJECT_ERROR)
            ->assertRaised();
    }

    public function testCanAssignProjectWhenRecordsAreAfterTheLockedPeriod(): void
    {
        $dto = $this->createLockedProjectDto('2020-06-30 23:59:59', '2020-07-01 00:00:00');

        $this->validator->validate($dto, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->assertNoViolation();
    }

    public function testCanAssignProjectWithoutLockDate(): void
    {
        $dto = $this->createLockedProjectDto(null, '2020-06-15 10:00:00');

        $this->validator->validate($dto, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->assertNoViolation();
    }

    public function testLockedProjectIsOnlyReportedOnce(): void
    {
        // several locked records must not produce one violation each
        $dto = $this->createLockedProjectDto('2020-06-30 23:59:59', '2020-06-15 10:00:00');
        $second = new Timesheet();
        $second->setBegin(new \DateTime('2020-06-16 10:00:00'));
        $entities = $dto->getEntities();
        $entities[] = $second;
        $dto->setEntities($entities);

        $this->validator->validate($dto, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        self::assertCount(1, $this->context->getViolations());
    }

    /**
     * @return array{0: Project, 1: Activity, 2: Team}
     */
    private function createTeamRestrictedProject(): array
    {
        $customer = new Customer('foo');
        $project = new Project();
        $project->setCustomer($customer);
        $team = new Team('foreign team');
        $team->addProject($project);
        $project->addTeam($team);

        $activity = new Activity();

        return [$project, $activity, $team];
    }

    public function testCannotAssignProjectOfForeignTeam(): void
    {
        [$project, $activity] = $this->createTeamRestrictedProject();

        $dto = new TimesheetMultiUpdateDTO();
        $dto->setProject($project);
        $dto->setActivity($activity);

        $this->validator->validate($dto, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('You are not allowed to use this project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetMultiUpdateConstraint::PROJECT_ACCESS_ERROR)
            ->assertRaised();
    }

    public function testCannotAssignActivityOfForeignTeam(): void
    {
        $activity = new Activity();
        $team = new Team('foreign team');
        $team->addActivity($activity);
        $activity->addTeam($team);

        $dto = new TimesheetMultiUpdateDTO();
        $dto->setActivity($activity);

        $this->validator->validate($dto, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->buildViolation('You are not allowed to use this activity.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetMultiUpdateConstraint::ACTIVITY_ACCESS_ERROR)
            ->assertRaised();
    }

    public function testCanAssignProjectOfOwnTeam(): void
    {
        [$project, $activity, $team] = $this->createTeamRestrictedProject();
        $team->addUser($this->user);

        $dto = new TimesheetMultiUpdateDTO();
        $dto->setProject($project);
        $dto->setActivity($activity);

        $this->validator->validate($dto, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->assertNoViolation();
    }

    public function testTeamAccessIsNotCheckedForUsersSeeingAllData(): void
    {
        $this->user->initCanSeeAllData(true);

        [$project, $activity] = $this->createTeamRestrictedProject();

        $dto = new TimesheetMultiUpdateDTO();
        $dto->setProject($project);
        $dto->setActivity($activity);

        $this->validator->validate($dto, new TimesheetMultiUpdateConstraint(['message' => 'myMessage']));

        $this->assertNoViolation();
    }
}
