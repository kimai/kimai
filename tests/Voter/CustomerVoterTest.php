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
class CustomerVoterTest extends AbstractVoterTestCase
{
    public function assertVote(User $user, $subject, $attribute, $result): void
    {
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = $this->getVoter(CustomerVoter::class);

        $actual = $sut->vote($token, $subject, [$attribute]);
        self::assertEquals($result, $actual, \sprintf('Failed voting "%s" for User with roles %s.', $attribute, implode(', ', $user->getRoles())));
    }

    public function testVote(): void
    {
        $userNoRole = self::getUser(0, 'foo');
        $userStandard = self::getUser(1, User::ROLE_USER);
        $userTeamlead = self::getUser(2, User::ROLE_TEAMLEAD);
        $userAdmin = self::getUser(3, User::ROLE_ADMIN);
        $userSuperAdmin = self::getUser(4, User::ROLE_SUPER_ADMIN);

        $result = VoterInterface::ACCESS_GRANTED;
        foreach ([$userAdmin, $userSuperAdmin] as $user) {
            $this->assertVote($user, new Customer('foo'), 'view', $result);
            $this->assertVote($user, new Customer('foo'), 'edit', $result);
            $this->assertVote($user, new Customer('foo'), 'budget', $result);
            $this->assertVote($user, new Customer('foo'), 'delete', $result);
        }

        $team = new Team('foo');
        $team->addTeamlead($userTeamlead);
        foreach ([$userTeamlead] as $user) {
            $customer = new Customer('foo');
            $team->addCustomer($customer);
            $this->assertVote($user, $customer, 'view', $result);
            $team->removeCustomer($customer);
        }

        $userTeamlead = self::getUser(2, User::ROLE_TEAMLEAD);

        $result = VoterInterface::ACCESS_DENIED;
        foreach ([$userNoRole, $userStandard] as $user) {
            $this->assertVote($user, new Customer('foo'), 'view', $result);
            $this->assertVote($user, new Customer('foo'), 'edit', $result);
            $this->assertVote($user, new Customer('foo'), 'budget', $result);
            $this->assertVote($user, new Customer('foo'), 'delete', $result);
        }

        foreach ([$userTeamlead] as $user) {
            $this->assertVote($user, new Customer('foo'), 'edit', $result);
            $this->assertVote($user, new Customer('foo'), 'budget', $result);
            $this->assertVote($user, new Customer('foo'), 'delete', $result);
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ([$userNoRole, $userStandard, $userTeamlead] as $user) {
            $this->assertVote($user, new Customer('foo'), 'view_customer', $result);
            $this->assertVote($user, new Customer('foo'), 'edit_customer', $result);
            $this->assertVote($user, new Customer('foo'), 'budget_customer', $result);
            $this->assertVote($user, new Customer('foo'), 'delete_customer', $result);
            $this->assertVote($user, new \stdClass(), 'view', $result);
            $this->assertVote($user, null, 'edit', $result);
            $this->assertVote($user, $user, 'delete', $result);
        }
    }

    public function testTeamlead(): void
    {
        $team = new Team('foo');
        $user = new User();
        $user->addRole(User::ROLE_TEAMLEAD);
        $team->addTeamlead($user);

        $customer = new Customer('foo');
        $customer->addTeam($team);

        $this->assertVote($user, $customer, 'edit', VoterInterface::ACCESS_GRANTED);
    }

    public function testTeamMember(): void
    {
        $team = new Team('foo');
        $user = new User();
        $user->addRole(User::ROLE_USER);
        $team->addTeamlead($user);

        $customer = new Customer('foo');
        $customer->addTeam($team);

        $this->assertVote($user, $customer, 'edit', VoterInterface::ACCESS_GRANTED);

        $team = new Team('foo');
        $user = new User();
        $user->addRole(User::ROLE_USER);
        $team->addUser($user);

        $customer = new Customer('foo');
        $customer->addTeam($team);

        $this->assertVote($user, $customer, 'edit', VoterInterface::ACCESS_GRANTED);
    }

    public function testAccess(): void
    {
        // ALLOW: customer has no teams
        $this->assertVote(new User(), new Customer('foo'), 'access', VoterInterface::ACCESS_GRANTED);

        // ALLOW: customer has no teams
        $user = new User();
        $user->addTeam(new Team('foo'));
        $this->assertVote($user, new Customer('foo'), 'access', VoterInterface::ACCESS_GRANTED);

        // ALLOW: user and customer are in the same team (as teamlead)
        $team = new Team('foo');
        $user = new User();
        $team->addTeamlead($user);

        $customer = new Customer('foo');
        $customer->addTeam($team);

        $this->assertVote($user, $customer, 'access', VoterInterface::ACCESS_GRANTED);

        // ALLOW: user and customer are in the same team (as member)
        $team = new Team('foo');
        $user = new User();
        $user->addTeam(new Team('foo'));
        $user->addTeam($team);

        $customer = new Customer('foo');
        $customer->addTeam($team);

        $this->assertVote($user, $customer, 'access', VoterInterface::ACCESS_GRANTED);

        // DENY: customer has a team, user not
        $customer = new Customer('foo');
        $customer->addTeam(new Team('foo'));

        $this->assertVote(new User(), $customer, 'access', VoterInterface::ACCESS_DENIED);

        // DENY: user and customer has a team are not in the same team
        $user = new User();
        $user->addTeam(new Team('foo'));
        $customer = new Customer('foo');
        $customer->addTeam(new Team('foo'));

        $this->assertVote($user, $customer, 'access', VoterInterface::ACCESS_DENIED);
    }
}
