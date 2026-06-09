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
use App\Security\RolePermissionManager;
use App\User\PermissionService;
use App\Validator\Constraints\TimesheetTeamAccess;
use App\Validator\Constraints\TimesheetTeamAccessValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<TimesheetTeamAccessValidator>
 */
#[CoversClass(TimesheetTeamAccess::class)]
#[CoversClass(TimesheetTeamAccessValidator::class)]
class TimesheetTeamAccessValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TimesheetTeamAccessValidator
    {
        return $this->createMyValidator();
    }

    /**
     * @param array<string, mixed> $originalData
     */
    protected function createMyValidator(
        array $originalData = [],
        ?User $user = null
    ): TimesheetTeamAccessValidator
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user ?? new User());

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('getPermissions')->willReturn([]);
        $permissionManager = new RolePermissionManager($permissionService, [], []);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getOriginalEntityData')->willReturn($originalData);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new TimesheetTeamAccessValidator($security, $permissionManager, $registry);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Timesheet(), new NotBlank());
    }

    public function testInvalidValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new NotBlank(), new TimesheetTeamAccess('myMessage'));
    }

    public function testTriggersForNewTimesheetWithInaccessibleProject(): void
    {
        $this->validator = $this->createMyValidator();
        $this->validator->initialize($this->context);

        $timesheet = new Timesheet();
        $timesheet->setProject($this->createRestrictedProject('restricted'));

        $this->validator->validate($timesheet, new TimesheetTeamAccess());

        $this->buildViolation('You are not allowed to use this project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetTeamAccess::PROJECT_ACCESS_ERROR)
            ->assertRaised();
    }

    public function testDoesNotReadOriginalDataForNewTimesheet(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('getPermissions')->willReturn([]);
        $permissionManager = new RolePermissionManager($permissionService, [], []);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::never())->method('getManagerForClass');

        $this->validator = new TimesheetTeamAccessValidator($security, $permissionManager, $registry);
        $this->validator->initialize($this->context);

        $timesheet = new Timesheet();
        $timesheet->setProject($this->createRestrictedProject('new-timesheet'));

        $this->validator->validate($timesheet, new TimesheetTeamAccess());

        $this->buildViolation('You are not allowed to use this project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetTeamAccess::PROJECT_ACCESS_ERROR)
            ->assertRaised();
    }

    public function testDoesNotTriggerForExistingTimesheetWithUnchangedProject(): void
    {
        $originalProject = $this->createRestrictedProject('restricted');

        $this->validator = $this->createMyValidator(['project' => $originalProject]);
        $this->validator->initialize($this->context);

        $timesheet = $this->createPersistedTimesheet();
        $timesheet->setProject($originalProject);

        $this->validator->validate($timesheet, new TimesheetTeamAccess());

        $this->assertNoViolation();
    }

    public function testTriggersForExistingTimesheetWithChangedProject(): void
    {
        $this->validator = $this->createMyValidator(['project' => $this->createProject('old')]);
        $this->validator->initialize($this->context);

        $timesheet = $this->createPersistedTimesheet();
        $timesheet->setProject($this->createRestrictedProject('new'));

        $this->validator->validate($timesheet, new TimesheetTeamAccess());

        $this->buildViolation('You are not allowed to use this project.')
            ->atPath('property.path.project')
            ->setCode(TimesheetTeamAccess::PROJECT_ACCESS_ERROR)
            ->assertRaised();
    }

    public function testTriggersForExistingTimesheetWithChangedActivity(): void
    {
        $this->validator = $this->createMyValidator(['activity' => $this->createActivity('old')]);
        $this->validator->initialize($this->context);

        $timesheet = $this->createPersistedTimesheet();
        $timesheet->setActivity($this->createRestrictedActivity('new'));

        $this->validator->validate($timesheet, new TimesheetTeamAccess());

        $this->buildViolation('You are not allowed to use this activity.')
            ->atPath('property.path.activity')
            ->setCode(TimesheetTeamAccess::ACTIVITY_ACCESS_ERROR)
            ->assertRaised();
    }

    public function testDoesNotTriggerForSuperAdmin(): void
    {
        $user = new User();
        $user->setRoles([User::ROLE_SUPER_ADMIN]);

        $this->validator = $this->createMyValidator([], $user);
        $this->validator->initialize($this->context);

        $timesheet = new Timesheet();
        $timesheet
            ->setProject($this->createRestrictedProject('restricted'))
            ->setActivity($this->createRestrictedActivity('restricted'))
        ;

        $this->validator->validate($timesheet, new TimesheetTeamAccess());

        $this->assertNoViolation();
    }

    public function testGetTargets(): void
    {
        $constraint = new TimesheetTeamAccess();
        self::assertEquals('class', $constraint->getTargets());
    }

    private function createPersistedTimesheet(): Timesheet
    {
        $timesheet = new Timesheet();
        $reflection = new \ReflectionClass($timesheet);
        $property = $reflection->getProperty('id');
        $property->setValue($timesheet, 1);

        return $timesheet;
    }

    private function createProject(string $name): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setCustomer(new Customer('customer-' . $name));

        return $project;
    }

    private function createActivity(string $name): Activity
    {
        $activity = new Activity();
        $activity->setName($name);

        return $activity;
    }

    private function createRestrictedProject(string $name): Project
    {
        $project = $this->createProject($name);
        $project->getCustomer()?->addTeam(new Team('customer-team-' . $name));

        return $project;
    }

    private function createRestrictedActivity(string $name): Activity
    {
        $activity = $this->createActivity($name);
        $activity->addTeam(new Team('activity-team-' . $name));

        return $activity;
    }
}
