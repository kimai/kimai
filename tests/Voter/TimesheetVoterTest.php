<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Configuration\ConfigLoaderInterface;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Timesheet\LockdownService;
use App\Voter\TimesheetVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(TimesheetVoter::class)]
class TimesheetVoterTest extends AbstractVoterTestCase
{
    protected function getVoter(string $voterClass): TimesheetVoter
    {
        return $this->getLockdownVoter();
    }

    private function assertVote(User $user, Timesheet $subject, string $attribute, int $result): void
    {
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = $this->getVoter(TimesheetVoter::class);

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    #[DataProvider('getTestData')]
    public function testVote(User $user, Timesheet $subject, string $attribute, int $result): void
    {
        $this->assertVote($user, $subject, $attribute, $result);
    }

    public static function getTestData()
    {
        $user0 = self::getTestUser(0, 'unknown');
        $user1 = self::getTestUser(1, User::ROLE_USER);
        $user2 = self::getTestUser(2, User::ROLE_TEAMLEAD);
        $user3 = self::getTestUser(3, User::ROLE_ADMIN);
        $user4 = self::getTestUser(4, User::ROLE_SUPER_ADMIN);

        $timesheet1 = self::getTimesheet($user1);
        $timesheet2 = self::getTimesheet($user2);
        $timesheet3 = self::getTimesheet($user3);
        $timesheet4 = self::getTimesheet($user4);
        $timesheet5 = self::getTimesheet($user2);
        $timesheet5->setExported(true);
        $timesheet6 = self::getTimesheet($user1);
        $timesheet6->getActivity()?->setVisible(false);

        $result = VoterInterface::ACCESS_GRANTED;
        $times = [
            [$user1, $timesheet1],
            [$user2, $timesheet2],
            [$user3, $timesheet3],
            [$user4, $timesheet4],
            [$user2, $timesheet1],
            [$user3, $timesheet2],
            [$user4, $timesheet3],
        ];
        foreach ($times as $timeEntry) {
            yield [$timeEntry[0], $timeEntry[1], 'start', $result];
            yield [$timeEntry[0], $timeEntry[1], 'stop', $result];
            yield [$timeEntry[0], $timeEntry[1], 'edit', $result];
            yield [$timeEntry[0], $timeEntry[1], 'delete', $result];
            yield [$timeEntry[0], $timeEntry[1], 'export', $result];
        }

        $result = VoterInterface::ACCESS_DENIED;
        $times = [
            [$user1, $timesheet4],
        ];
        foreach ($times as $timeEntry) {
            yield [$timeEntry[0], $timeEntry[1], 'start', $result];
            yield [$timeEntry[0], $timeEntry[1], 'stop', $result];
            yield [$timeEntry[0], $timeEntry[1], 'edit', $result];
            yield [$timeEntry[0], $timeEntry[1], 'delete', $result];
            yield [$timeEntry[0], $timeEntry[1], 'export', $result];
        }
    }

    #[DataProvider('getLockDownTestData')]
    public function testWithLockdown(string $permission, int $expected, string $beginModifier, string $lockdownBegin, string $lockdownEnd, ?string $lockdownGrace): void
    {
        $user = self::getTestUser(1, User::ROLE_USER);

        $begin = new \DateTime('now');
        $begin->modify($beginModifier);

        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);
        $timesheet->setUser($user);

        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = $this->getLockdownVoter($lockdownBegin, $lockdownEnd, $lockdownGrace);

        self::assertEquals($expected, $sut->vote($token, $timesheet, [$permission]));
    }

    public static function getLockDownTestData()
    {
        yield ['view', VoterInterface::ACCESS_GRANTED, '+1 days', 'first day of this month', 'last day of this month', '+10 days'];
        yield ['start', VoterInterface::ACCESS_DENIED, '+1 days', 'first day of this month', 'last day of this month', '+10 days'];
        yield ['duplicate', VoterInterface::ACCESS_DENIED, '+1 days', 'first day of this month', 'last day of this month', '+10 days'];
        yield ['delete', VoterInterface::ACCESS_GRANTED, '+1 days', 'first day of this month', 'last day of this month', '+10 days'];
        yield ['edit', VoterInterface::ACCESS_DENIED, '-50 days', 'first day of last month', 'last day of last month', '+1 days'];
        yield ['duplicate', VoterInterface::ACCESS_DENIED, '-50 days', 'first day of last month', 'last day of last month', '+1 days'];
        yield ['delete', VoterInterface::ACCESS_DENIED, '-50 days', 'first day of last month', 'last day of last month', '+1 days'];
    }

    public function testSpecialCases(): void
    {
        $user1 = self::getTestUser(1, User::ROLE_USER);
        $user2 = self::getTestUser(2, User::ROLE_TEAMLEAD);
        $user3 = self::getTestUser(3, User::ROLE_ADMIN);
        $user4 = self::getTestUser(4, User::ROLE_SUPER_ADMIN);

        // unknown attribute
        $timesheet = self::getTimesheet($user3);
        $this->assertVote($user3, $timesheet, 'edit2', VoterInterface::ACCESS_ABSTAIN);

        $timesheet = self::getTimesheet($user2);
        $timesheet->setExported(true);
        // edit exported timesheet disallowed for teamleads
        $this->assertVote($user2, $timesheet, 'edit', VoterInterface::ACCESS_DENIED);
        $this->assertVote($user2, $timesheet, 'delete', VoterInterface::ACCESS_DENIED);
        // but allowed for admins
        $this->assertVote($user4, $timesheet, 'edit', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($user4, $timesheet, 'delete', VoterInterface::ACCESS_GRANTED);

        // hidden activities might not be started
        $timesheet = self::getTimesheet($user1);
        $timesheet->getActivity()?->setVisible(false);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);

        // hidden projects might not be started
        $timesheet = self::getTimesheet($user1);
        $timesheet->getProject()?->setVisible(false);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);

        // hidden customers might not be started
        $timesheet = self::getTimesheet($user1);
        $timesheet->getProject()?->getCustomer()?->setVisible(false);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);
        // cannot start timesheet without activity
        $timesheet = new Timesheet();
        $project = new Project();
        $project->setCustomer(new Customer('foo'));
        $timesheet->setUser($user2)->setProject($project);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);
        // cannot start timesheet without project
        $timesheet = new Timesheet();
        $timesheet->setUser($user2)->setActivity(new Activity());
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);
    }

    private static function getTimesheet($user): Timesheet
    {
        $activity = new Activity();
        $project = new Project();
        $customer = new Customer('foo');
        $activity->setProject($project);
        $project->setCustomer($customer);

        $timesheet = new Timesheet();
        $timesheet->setUser($user);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        return $timesheet;
    }

    private static function getTestUser(int $id, string $role): User
    {
        $user = self::createStub(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getRoles')->willReturn([$role]);
        $user->method('getTeams')->willReturn([]);
        $user->method('getTimezone')->willReturn(date_default_timezone_get());

        return $user;
    }

    private function getLockdownVoter(?string $lockdownBegin = null, ?string $lockdownEnd = null, ?string $lockdownGrace = null): TimesheetVoter
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, [
            'timesheet' => [
                'rules' => [
                    'lockdown_period_start' => $lockdownBegin,
                    'lockdown_period_end' => $lockdownEnd,
                    'lockdown_grace_period' => $lockdownGrace,
                ],
            ]
        ]);

        return new TimesheetVoter($this->getRolePermissionManager(), new LockdownService($config));
    }

    /**
     * @return array<string>
     */
    public static function teamCheckedAttributes(): array
    {
        // Attributes that go through the new checkTeamAccessTimesheet() gate when
        // accessing another user's timesheet. 'start' is excluded because it
        // additionally requires visibility on project/activity (canStart()).
        return ['view', 'edit', 'delete', 'export', 'view_rate', 'edit_rate', 'edit_export', 'edit_billable', 'stop'];
    }

    public function testOwnerCanAccessOwnTimesheetEvenWithRestrictiveTeams(): void
    {
        // Owner short-circuit: the team gate must NOT apply to the user's own timesheet.
        $owner = self::getUser(1, User::ROLE_USER);

        $customer = new Customer('Acme');
        $customer->addTeam(new Team('locked customer team'));

        $project = new Project();
        $project->setCustomer($customer);
        $project->addTeam(new Team('locked project team'));

        $activity = new Activity();
        $activity->setProject($project);
        $activity->addTeam(new Team('locked activity team'));

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->assertVote($owner, $timesheet, 'view', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($owner, $timesheet, 'edit', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($owner, $timesheet, 'delete', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($owner, $timesheet, 'export', VoterInterface::ACCESS_GRANTED);
    }

    public function testTeamleadDeniedWhenOnlyPlainMemberOfOwnerTeam(): void
    {
        // Headline new behaviour: a TEAMLEAD role with view_other_timesheet must
        // not access another user's timesheet by being a plain team member —
        // they must be the team's teamlead.
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $sharedTeam = new Team('shared');
        $sharedTeam->addUser($owner);
        $sharedTeam->addUser($requester);

        $timesheet = self::getTimesheetFor($owner);

        foreach (self::teamCheckedAttributes() as $attribute) {
            $this->assertVote($requester, $timesheet, $attribute, VoterInterface::ACCESS_DENIED);
        }
    }

    public function testTeamleadGrantedWhenTeamleadOfOwnerTeam(): void
    {
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $sharedTeam = new Team('shared');
        $sharedTeam->addUser($owner);
        $sharedTeam->addTeamlead($requester);

        $timesheet = self::getTimesheetFor($owner);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($requester, $timesheet, 'edit', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($requester, $timesheet, 'delete', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($requester, $timesheet, 'export', VoterInterface::ACCESS_GRANTED);
    }

    public function testTeamleadDeniedWhenOnlyTeamleadOfUnrelatedTeam(): void
    {
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $ownerTeam = new Team('owner team');
        $ownerTeam->addUser($owner);

        $unrelated = new Team('unrelated');
        $unrelated->addTeamlead($requester);

        $timesheet = self::getTimesheetFor($owner);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_DENIED);
        $this->assertVote($requester, $timesheet, 'edit', VoterInterface::ACCESS_DENIED);
    }

    public function testTeamleadGrantedWhenOwnerHasNoTeams(): void
    {
        // Owner has no teams -> teamlead gate is permissive.
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $timesheet = self::getTimesheetFor($owner);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($requester, $timesheet, 'edit', VoterInterface::ACCESS_GRANTED);
    }

    public function testTeamleadDeniedWhenCustomerTeamBlocks(): void
    {
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        // Even though requester is a teamlead of the owner's team, the customer
        // team blocks access.
        $sharedTeam = new Team('shared');
        $sharedTeam->addUser($owner);
        $sharedTeam->addTeamlead($requester);

        $customer = new Customer('Acme');
        $customer->addTeam(new Team('locked customer team'));

        $project = new Project();
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_DENIED);
        $this->assertVote($requester, $timesheet, 'edit', VoterInterface::ACCESS_DENIED);
        $this->assertVote($requester, $timesheet, 'delete', VoterInterface::ACCESS_DENIED);
    }

    public function testTeamleadDeniedWhenProjectTeamBlocks(): void
    {
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $sharedTeam = new Team('shared');
        $sharedTeam->addUser($owner);
        $sharedTeam->addTeamlead($requester);

        $project = new Project();
        $project->setCustomer(new Customer('Acme'));
        $project->addTeam(new Team('locked project team'));

        $activity = new Activity();
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_DENIED);
    }

    public function testTeamleadDeniedWhenActivityTeamBlocks(): void
    {
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $sharedTeam = new Team('shared');
        $sharedTeam->addUser($owner);
        $sharedTeam->addTeamlead($requester);

        $project = new Project();
        $project->setCustomer(new Customer('Acme'));

        $activity = new Activity();
        $activity->setProject($project);
        $activity->addTeam(new Team('locked activity team'));

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_DENIED);
    }

    public function testTeamleadGrantedThroughFullTeamChainAsMemberAndTeamlead(): void
    {
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $customerTeam = new Team('customer team');
        $customer = new Customer('Acme');
        $customer->addTeam($customerTeam);
        $customerTeam->addUser($requester);

        $projectTeam = new Team('project team');
        $project = new Project();
        $project->setCustomer($customer);
        $project->addTeam($projectTeam);
        $projectTeam->addUser($requester);

        $activityTeam = new Team('activity team');
        $activity = new Activity();
        $activity->setProject($project);
        $activity->addTeam($activityTeam);
        $activityTeam->addUser($requester);

        $ownerTeam = new Team('owner team');
        $ownerTeam->addUser($owner);
        $ownerTeam->addTeamlead($requester);

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($requester, $timesheet, 'edit', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($requester, $timesheet, 'delete', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($requester, $timesheet, 'export', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($requester, $timesheet, 'view_rate', VoterInterface::ACCESS_GRANTED);
    }

    public function testSuperAdminCanAccessOtherTimesheetDespiteRestrictiveTeams(): void
    {
        // SUPER_ADMIN gets canSeeAllData via isSuperAdmin() — short-circuits every team gate.
        $owner = self::getUser(1, User::ROLE_USER);
        $admin = self::getUser(99, User::ROLE_SUPER_ADMIN);

        $ownerTeam = new Team('owner team');
        $ownerTeam->addUser($owner);

        $customer = new Customer('Acme');
        $customer->addTeam(new Team('locked customer team'));

        $project = new Project();
        $project->setCustomer($customer);
        $project->addTeam(new Team('locked project team'));

        $activity = new Activity();
        $activity->setProject($project);
        $activity->addTeam(new Team('locked activity team'));

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        self::assertTrue($admin->canSeeAllData());
        $this->assertVote($admin, $timesheet, 'view', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($admin, $timesheet, 'edit', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($admin, $timesheet, 'delete', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($admin, $timesheet, 'export', VoterInterface::ACCESS_GRANTED);
    }

    public function testAdminWithoutCanSeeAllDataIsBlockedByCustomerTeam(): void
    {
        // ROLE_ADMIN does NOT automatically have canSeeAllData() — only SUPER_ADMIN does.
        // An ADMIN without the flag is subject to the team gate just like everyone else.
        // Documents that the new check tightens admin access too.
        $owner = self::getUser(1, User::ROLE_USER);
        $admin = self::getUser(3, User::ROLE_ADMIN);
        self::assertFalse($admin->canSeeAllData());

        $customer = new Customer('Acme');
        $customer->addTeam(new Team('locked customer team'));

        $project = new Project();
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->assertVote($admin, $timesheet, 'view', VoterInterface::ACCESS_DENIED);
        $this->assertVote($admin, $timesheet, 'edit', VoterInterface::ACCESS_DENIED);
    }

    public function testAdminCanSeeAllDataBypassesAllTeamGates(): void
    {
        // Activating canSeeAllData on a non-super-admin must restore full access.
        $owner = self::getUser(1, User::ROLE_USER);
        $admin = self::getUser(3, User::ROLE_ADMIN);
        $admin->initCanSeeAllData(true);

        $ownerTeam = new Team('owner team');
        $ownerTeam->addUser($owner);

        $customer = new Customer('Acme');
        $customer->addTeam(new Team('locked customer team'));

        $project = new Project();
        $project->setCustomer($customer);
        $project->addTeam(new Team('locked project team'));

        $activity = new Activity();
        $activity->setProject($project);
        $activity->addTeam(new Team('locked activity team'));

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        $this->assertVote($admin, $timesheet, 'view', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($admin, $timesheet, 'edit', VoterInterface::ACCESS_GRANTED);
    }

    public function testRoleUserDeniedOnOtherTimesheetEvenWhenTeamGatePasses(): void
    {
        // ROLE_USER has only *_own_timesheet permissions. The team gate may pass,
        // but the role still has no _other permission → denied. Defence-in-depth check.
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_USER);

        // No teams anywhere — team gate is fully permissive.
        $timesheet = self::getTimesheetFor($owner);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_DENIED);
        $this->assertVote($requester, $timesheet, 'edit', VoterInterface::ACCESS_DENIED);
        $this->assertVote($requester, $timesheet, 'export', VoterInterface::ACCESS_DENIED);
    }

    public function testTeamleadGrantedWhenTeamleadOfOneOfMultipleOwnerTeams(): void
    {
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $teamA = new Team('A');
        $teamB = new Team('B');
        $teamA->addUser($owner);
        $teamB->addUser($owner);

        // Plain member of A, teamlead of B — granted because at least one match.
        $teamA->addUser($requester);
        $teamB->addTeamlead($requester);

        $timesheet = self::getTimesheetFor($owner);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_GRANTED);
    }

    public function testTeamleadDeniedWhenMemberOfAllButTeamleadOfNoneOfOwnerTeams(): void
    {
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $teamA = new Team('A');
        $teamB = new Team('B');
        $teamA->addUser($owner);
        $teamB->addUser($owner);
        $teamA->addUser($requester);
        $teamB->addUser($requester);

        $timesheet = self::getTimesheetFor($owner);

        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_DENIED);
    }

    public function testTeamleadGrantedWhenOwnerTeamHasNoTeamsAndChainsAreMemberOnly(): void
    {
        // Confirms that the customer/project/activity gate uses the simpler
        // "is member" rule (not teamlead) — only the final owner-team gate
        // requires teamlead.
        $owner = self::getUser(1, User::ROLE_USER);
        $requester = self::getUser(2, User::ROLE_TEAMLEAD);

        $customerTeam = new Team('customer team');
        $customer = new Customer('Acme');
        $customer->addTeam($customerTeam);
        $customerTeam->addUser($requester); // plain member is enough here

        $project = new Project();
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        // Owner has no teams -> teamlead gate is permissive.
        $this->assertVote($requester, $timesheet, 'view', VoterInterface::ACCESS_GRANTED);
    }

    private static function getTimesheetFor(User $owner, ?Team $customerTeam = null, ?Team $projectTeam = null, ?Team $activityTeam = null): Timesheet
    {
        $customer = new Customer('Acme');
        if ($customerTeam !== null) {
            $customer->addTeam($customerTeam);
        }
        $project = new Project();
        $project->setCustomer($customer);
        if ($projectTeam !== null) {
            $project->addTeam($projectTeam);
        }
        $activity = new Activity();
        $activity->setProject($project);
        if ($activityTeam !== null) {
            $activity->addTeam($activityTeam);
        }

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);

        return $timesheet;
    }
}
