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
    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $subject, $attribute, $result)
    {
        $this->assertVote($user, $subject, $attribute, $result);
    }

    protected function assertVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(CustomerVoter::class, $user);

        $this->assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    public function getTestData()
    {
        $user0 = $this->getUser(0, null);
        $user1 = $this->getUser(1, User::ROLE_USER);
        $user2 = $this->getUser(2, User::ROLE_TEAMLEAD);
        $user3 = $this->getUser(3, User::ROLE_ADMIN);
        $user4 = $this->getUser(4, User::ROLE_SUPER_ADMIN);

        $result = VoterInterface::ACCESS_GRANTED;
        foreach ([$user3, $user4] as $user) {
            yield [$user, new Customer(), 'view', $result];
            yield [$user, new Customer(), 'edit', $result];
            yield [$user, new Customer(), 'budget', $result];
            yield [$user, new Customer(), 'delete', $result];
        }

        foreach ([$user2] as $user) {
            yield [$user, new Customer(), 'view', $result];
        }

        $result = VoterInterface::ACCESS_DENIED;
        foreach ([$user0, $user1] as $user) {
            yield [$user, new Customer(), 'view', $result];
            yield [$user, new Customer(), 'edit', $result];
            yield [$user, new Customer(), 'budget', $result];
            yield [$user, new Customer(), 'delete', $result];
        }

        foreach ([$user2] as $user) {
            yield [$user, new Customer(), 'edit', $result];
            yield [$user, new Customer(), 'budget', $result];
            yield [$user, new Customer(), 'delete', $result];
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ([$user0, $user1, $user2] as $user) {
            yield [$user, new Customer(), 'view_customer', $result];
            yield [$user, new Customer(), 'edit_customer', $result];
            yield [$user, new Customer(), 'budget_customer', $result];
            yield [$user, new Customer(), 'delete_customer', $result];
            yield [$user, new \stdClass(), 'view', $result];
            yield [$user, null, 'edit', $result];
            yield [$user, $user, 'delete', $result];
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
