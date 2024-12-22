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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\TimesheetVoter
 */
class TimesheetVoterTest extends AbstractVoterTestCase
{
    protected function getVoter(string $voterClass): TimesheetVoter
    {
        return $this->getLockdownVoter();
    }

    protected function assertVote(User $user, $subject, $attribute, $result): void
    {
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = $this->getVoter(TimesheetVoter::class);

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    public function testVote(): void
    {
        foreach ($this->getTestData() as $row) {
            $this->assertVote($row[0], $row[1], $row[2], $row[3]);
        }
    }

    public function getTestData()
    {
        $user0 = $this->getTestUser(0, 'unknown');
        $user1 = $this->getTestUser(1, User::ROLE_USER);
        $user2 = $this->getTestUser(2, User::ROLE_TEAMLEAD);
        $user3 = $this->getTestUser(3, User::ROLE_ADMIN);
        $user4 = $this->getTestUser(4, User::ROLE_SUPER_ADMIN);

        $timesheet1 = $this->getTimesheet($user1);
        $timesheet2 = $this->getTimesheet($user2);
        $timesheet3 = $this->getTimesheet($user3);
        $timesheet4 = $this->getTimesheet($user4);
        $timesheet5 = $this->getTimesheet($user2);
        $timesheet5->setExported(true);
        $timesheet6 = $this->getTimesheet($user1);
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

    /**
     * @dataProvider getLockDownTestData
     */
    public function testWithLockdown(string $permission, int $expected, string $beginModifier, string $lockdownBegin, string $lockdownEnd, ?string $lockdownGrace): void
    {
        $user = $this->getTestUser(1, User::ROLE_USER);

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
        $user1 = $this->getTestUser(1, User::ROLE_USER);
        $user2 = $this->getTestUser(2, User::ROLE_TEAMLEAD);
        $user3 = $this->getTestUser(3, User::ROLE_ADMIN);
        $user4 = $this->getTestUser(4, User::ROLE_SUPER_ADMIN);

        // unknown attribute
        $timesheet = $this->getTimesheet($user3);
        $this->assertVote($user3, $timesheet, 'edit2', VoterInterface::ACCESS_ABSTAIN);

        $timesheet = $this->getTimesheet($user2);
        $timesheet->setExported(true);
        // edit exported timesheet disallowed for teamleads
        $this->assertVote($user2, $timesheet, 'edit', VoterInterface::ACCESS_DENIED);
        $this->assertVote($user2, $timesheet, 'delete', VoterInterface::ACCESS_DENIED);
        // but allowed for admins
        $this->assertVote($user4, $timesheet, 'edit', VoterInterface::ACCESS_GRANTED);
        $this->assertVote($user4, $timesheet, 'delete', VoterInterface::ACCESS_GRANTED);

        // hidden activities might not be started
        $timesheet = $this->getTimesheet($user1);
        $timesheet->getActivity()?->setVisible(false);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);

        // hidden projects might not be started
        $timesheet = $this->getTimesheet($user1);
        $timesheet->getProject()?->setVisible(false);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);

        // hidden customers might not be started
        $timesheet = $this->getTimesheet($user1);
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

    protected function getTimesheet($user): Timesheet
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($user);

        $activity = new Activity();
        $project = new Project();
        $activity->setProject($project);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);
        $customer = new Customer('foo');
        $project->setCustomer($customer);

        return $timesheet;
    }

    /**
     * @param int $id
     * @param string $role
     * @return User
     */
    protected function getTestUser(int $id, string $role): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getRoles')->willReturn([$role]);
        $user->method('getTimezone')->willReturn(date_default_timezone_get());

        return $user;
    }

    protected function getLockdownVoter(?string $lockdownBegin = null, ?string $lockdownEnd = null, ?string $lockdownGrace = null): TimesheetVoter
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
