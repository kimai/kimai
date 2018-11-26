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
    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(TimesheetVoter::class, $user);

        $this->assertEquals($result, $sut->vote($token, $subject, [$attribute]));
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
        }
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
