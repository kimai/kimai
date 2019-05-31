<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Voter\TimesheetVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\TimesheetVoter
 */
class TimesheetVoterTest extends AbstractVoterTest
{
    protected function assertVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(TimesheetVoter::class, $user);

        $this->assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $subject, $attribute, $result)
    {
        $this->assertVote($user, $subject, $attribute, $result);
    }

    public function getTestData()
    {
        $user0 = $this->getUser(0, null);
        $user1 = $this->getUser(1, User::ROLE_USER);
        $user2 = $this->getUser(2, User::ROLE_TEAMLEAD);
        $user3 = $this->getUser(3, User::ROLE_ADMIN);
        $user4 = $this->getUser(4, User::ROLE_SUPER_ADMIN);

        $timesheet1 = $this->getTimesheet($user1);
        $timesheet2 = $this->getTimesheet($user2);
        $timesheet3 = $this->getTimesheet($user3);
        $timesheet4 = $this->getTimesheet($user4);
        $timesheet5 = $this->getTimesheet($user2);
        $timesheet5->setExported(true);
        $timesheet6 = $this->getTimesheet($user1);
        $timesheet6->getActivity()->setVisible(false);

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

    public function testSpecialCases()
    {
        $user1 = $this->getUser(1, User::ROLE_USER);
        $user2 = $this->getUser(2, User::ROLE_TEAMLEAD);
        $user3 = $this->getUser(3, User::ROLE_ADMIN);
        $user4 = $this->getUser(4, User::ROLE_SUPER_ADMIN);

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
        $timesheet->getActivity()->setVisible(false);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);

        // hidden projects might not be started
        $timesheet = $this->getTimesheet($user1);
        $timesheet->getProject()->setVisible(false);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);

        // hidden customers might not be started
        $timesheet = $this->getTimesheet($user1);
        $timesheet->getProject()->getCustomer()->setVisible(false);
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);
        // cannot start timesheet without activity
        $timesheet = new Timesheet();
        $timesheet->setUser($user2)->setProject(new Project());
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);
        // cannot start timesheet without project
        $timesheet = new Timesheet();
        $timesheet->setUser($user2)->setActivity(new Activity());
        $this->assertVote($user2, $timesheet, 'start', VoterInterface::ACCESS_DENIED);
    }

    protected function getTimesheet($user)
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($user);

        $activity = new Activity();
        $project = new Project();
        $activity->setProject($project);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);
        $customer = new Customer();
        $project->setCustomer($customer);

        return $timesheet;
    }

    /**
     * @param $id
     * @param $role
     * @return User
     */
    protected function getUser($id, $role)
    {
        $user = $this->getMockBuilder(User::class)->getMock();
        $user->method('getId')->willReturn($id);
        $user->method('getRoles')->willReturn([$role]);

        return $user;
    }
}
