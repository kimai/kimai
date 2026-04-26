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
}
