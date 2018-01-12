<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\User;
use \PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use App\Entity\Customer;
use App\Entity\Timesheet;
use App\Voter\TimesheetVoter;

/**
 * @covers \App\Voter\TimesheetVoter
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetVoterTest extends TestCase
{

    /**
     * @dataProvider getTestData
     */
    public function testCustomerIsDisallowed($user, $allow, $subject, $attributes, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());

        $accessManager = $this->getMockBuilder(AccessDecisionManagerInterface::class)->getMock();
        $accessManager->method('decide')->willReturn($allow);

        $sut = new TimesheetVoter($accessManager);

        $this->assertEquals($result, $sut->vote($token, $subject, $attributes));
    }

    public function getTestData()
    {
        $user0 = $this->getUser(0, 'ROLE_CUSTOMER');
        $user1 =  $this->getUser(1, 'ROLE_USER');
        $user2 =  $this->getUser(1, 'ROLE_TEAMLEAD');

        return [
            [$user0, false, new Customer(), ['edit'], VoterInterface::ACCESS_ABSTAIN],
            [$user1, false, $this->getTimesheet($user1), ['edit'], VoterInterface::ACCESS_GRANTED],
            [$user1, false, $this->getTimesheet($user0), ['edit'], VoterInterface::ACCESS_DENIED],
            [$user2, true, $this->getTimesheet($user1), ['edit'], VoterInterface::ACCESS_GRANTED],
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
