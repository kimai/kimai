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
use App\Entity\Timesheet;
use App\Entity\User;
use App\Security\AclDecisionManager;
use App\Voter\TimesheetVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\TimesheetVoter
 */
class TimesheetVoterTest extends TestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testVote($user, $roles, $allow, $subject, $attributes, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $roles);

        $accessManager = $this->getMockBuilder(AclDecisionManager::class)->disableOriginalConstructor()->getMock();
        $accessManager->method('isFullyAuthenticated')->willReturn($allow);
        $accessManager->method('hasRole')->willReturn($allow);

        $sut = new TimesheetVoter($accessManager);

        $this->assertEquals($result, $sut->vote($token, $subject, $attributes));
    }

    public function getTestData()
    {
        $user0 = $this->getUser(0, User::ROLE_CUSTOMER);
        $user1 = $this->getUser(1, User::ROLE_USER);
        $user2 = $this->getUser(2, User::ROLE_TEAMLEAD);

        return [
            [$user0, $user0->getRoles(), false, new Customer(), [TimesheetVoter::EDIT], VoterInterface::ACCESS_ABSTAIN],
            [$user1, $user1->getRoles(), false, $this->getTimesheet($user1), [TimesheetVoter::EDIT], VoterInterface::ACCESS_GRANTED],
            [$user1, $user1->getRoles(), false, $this->getTimesheet($user0), [TimesheetVoter::EDIT], VoterInterface::ACCESS_DENIED],
            [$user2, $user2->getRoles(), true, $this->getTimesheet($user1), [TimesheetVoter::EDIT], VoterInterface::ACCESS_GRANTED],
            ['foo', [], false, $this->getTimesheet($user1), [TimesheetVoter::EDIT], VoterInterface::ACCESS_DENIED],
            [$user2, $user2->getRoles(), true, new Activity(), [TimesheetVoter::EDIT], VoterInterface::ACCESS_ABSTAIN],
            [$user2, $user2->getRoles(), true, $this->getTimesheet($user2), [TimesheetVoter::VIEW], VoterInterface::ACCESS_GRANTED],
            [$user1, $user1->getRoles(), false, $this->getTimesheet($user2), [TimesheetVoter::VIEW], VoterInterface::ACCESS_DENIED],
        ];
    }

    protected function getTimesheet($user)
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($user);

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
