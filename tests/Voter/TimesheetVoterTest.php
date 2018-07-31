<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Customer;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Voter\TimesheetVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\TimesheetVoter
 */
class TimesheetVoterTest extends TestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testCustomerIsDisallowed(User $user, $allow, $subject, $attributes, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());

        $accessManager = $this->getMockBuilder(AccessDecisionManagerInterface::class)->getMock();
        $accessManager->method('decide')->willReturn($allow);

        $sut = new TimesheetVoter($accessManager);

        $this->assertEquals($result, $sut->vote($token, $subject, $attributes));
    }

    public function getTestData()
    {
        $user0 = $this->getUser(0, User::ROLE_CUSTOMER);
        $user1 = $this->getUser(1, User::ROLE_USER);
        $user2 = $this->getUser(1, User::ROLE_TEAMLEAD);

        return [
            [$user0, false, new Customer(), [TimesheetVoter::EDIT], VoterInterface::ACCESS_ABSTAIN],
            [$user1, false, $this->getTimesheet($user1), [TimesheetVoter::EDIT], VoterInterface::ACCESS_GRANTED],
            [$user1, false, $this->getTimesheet($user0), [TimesheetVoter::EDIT], VoterInterface::ACCESS_DENIED],
            [$user2, true, $this->getTimesheet($user1), [TimesheetVoter::EDIT], VoterInterface::ACCESS_GRANTED],
        ];
    }

    protected function getTimesheet($user)
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($user);

        return $timesheet;
    }

    protected function getUser($id, $role)
    {
        $user = $this->getMockBuilder(User::class)->getMock();
        $user->method('getId')->willReturn($id);
        $user->method('getRoles')->willReturn([$role]);

        return $user;
    }
}
