<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Customer;
use App\Entity\Team;
use App\Entity\User;
use App\Voter\CustomerVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\CustomerVoter
 */
class CustomerVoterTest extends AbstractVoterTest
{
    protected function assertVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(CustomerVoter::class, $user);

        $actual = $sut->vote($token, $subject, [$attribute]);
        $this->assertEquals($result, $actual, sprintf('Failed voting "%s" for User with roles %s.', $attribute, implode(', ', $user->getRoles())));
    }

    public function testVote()
    {
        $userNoRole = $this->getUser(0, 'foo');
        $userStandard = $this->getUser(1, User::ROLE_USER);
        $userTeamlead = $this->getUser(2, User::ROLE_TEAMLEAD);
        $userAdmin = $this->getUser(3, User::ROLE_ADMIN);
        $userSuperAdmin = $this->getUser(4, User::ROLE_SUPER_ADMIN);

        $result = VoterInterface::ACCESS_GRANTED;
        foreach ([$userAdmin, $userSuperAdmin] as $user) {
            $this->assertVote($user, new Customer(), 'view', $result);
            $this->assertVote($user, new Customer(), 'edit', $result);
            $this->assertVote($user, new Customer(), 'budget', $result);
            $this->assertVote($user, new Customer(), 'delete', $result);
        }

        $team = new Team();
        $team->setTeamLead($userTeamlead);
        foreach ([$userTeamlead] as $user) {
            $customer = new Customer();
            $team->addCustomer($customer);
            $this->assertVote($user, $customer, 'view', $result);
            $team->removeCustomer($customer);
        }

        $userTeamlead = $this->getUser(2, User::ROLE_TEAMLEAD);

        $result = VoterInterface::ACCESS_DENIED;
        foreach ([$userNoRole, $userStandard] as $user) {
            $this->assertVote($user, new Customer(), 'view', $result);
            $this->assertVote($user, new Customer(), 'edit', $result);
            $this->assertVote($user, new Customer(), 'budget', $result);
            $this->assertVote($user, new Customer(), 'delete', $result);
        }

        foreach ([$userTeamlead] as $user) {
            $this->assertVote($user, new Customer(), 'edit', $result);
            $this->assertVote($user, new Customer(), 'budget', $result);
            $this->assertVote($user, new Customer(), 'delete', $result);
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ([$userNoRole, $userStandard, $userTeamlead] as $user) {
            $this->assertVote($user, new Customer(), 'view_customer', $result);
            $this->assertVote($user, new Customer(), 'edit_customer', $result);
            $this->assertVote($user, new Customer(), 'budget_customer', $result);
            $this->assertVote($user, new Customer(), 'delete_customer', $result);
            $this->assertVote($user, new \stdClass(), 'view', $result);
            $this->assertVote($user, null, 'edit', $result);
            $this->assertVote($user, $user, 'delete', $result);
        }
    }

    public function testTeamlead()
    {
        $team = new Team();
        $user = new User();
        $user->addRole(User::ROLE_TEAMLEAD);
        $team->setTeamLead($user);

        $customer = new Customer();
        $customer->addTeam($team);

        $this->assertVote($user, $customer, 'edit', VoterInterface::ACCESS_GRANTED);
    }

    public function testTeamMember()
    {
        $team = new Team();
        $user = new User();
        $user->addRole(User::ROLE_USER);
        $team->setTeamLead($user);

        $customer = new Customer();
        $customer->addTeam($team);

        $this->assertVote($user, $customer, 'edit', VoterInterface::ACCESS_GRANTED);

        $team = new Team();
        $user = new User();
        $user->addRole(User::ROLE_USER);
        $team->addUser($user);

        $customer = new Customer();
        $customer->addTeam($team);

        $this->assertVote($user, $customer, 'edit', VoterInterface::ACCESS_GRANTED);
    }
}
