<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Voter\ProjectVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\ProjectVoter
 */
class ProjectVoterTest extends AbstractVoterTest
{
    protected function assertVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(ProjectVoter::class, $user);

        if ($subject instanceof Project && null === $subject->getCustomer()) {
            $subject->setCustomer(new Customer());
        }

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
            $this->assertVote($user, new Project(), 'view', $result);
            $this->assertVote($user, new Project(), 'edit', $result);
            $this->assertVote($user, new Project(), 'budget', $result);
            $this->assertVote($user, new Project(), 'delete', $result);
        }

        $team = new Team();
        $team->setTeamLead($userTeamlead);
        foreach ([$userTeamlead] as $user) {
            $project = new Project();
            $team->addProject($project);
            $this->assertVote($user, $project, 'view', $result);
            $team->removeProject($project);
        }

        $userTeamlead = $this->getUser(2, User::ROLE_TEAMLEAD);

        $result = VoterInterface::ACCESS_DENIED;
        foreach ([$userNoRole, $userStandard] as $user) {
            $this->assertVote($user, new Project(), 'view', $result);
            $this->assertVote($user, new Project(), 'edit', $result);
            $this->assertVote($user, new Project(), 'budget', $result);
            $this->assertVote($user, new Project(), 'delete', $result);
        }

        foreach ([$userTeamlead] as $user) {
            $this->assertVote($user, new Project(), 'view', $result);
            $this->assertVote($user, new Project(), 'edit', $result);
            $this->assertVote($user, new Project(), 'budget', $result);
            $this->assertVote($user, new Project(), 'delete', $result);
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ([$userNoRole, $userStandard, $userTeamlead] as $user) {
            $this->assertVote($user, new Project(), 'create_project', $result);
            $this->assertVote($user, new Project(), 'view_project', $result);
            $this->assertVote($user, new Project(), 'edit_project', $result);
            $this->assertVote($user, new Project(), 'budget_project', $result);
            $this->assertVote($user, new Project(), 'delete_project', $result);
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

        $project = new Project();
        $customer = new Customer();
        $project->setCustomer($customer);
        $customer->addTeam($team);

        $this->assertVote($user, $project, 'edit', VoterInterface::ACCESS_GRANTED);

        $project = new Project();
        $customer = new Customer();
        $project->setCustomer($customer);
        $project->addTeam($team);

        $this->assertVote($user, $project, 'edit', VoterInterface::ACCESS_GRANTED);
    }

    public function testTeamMember()
    {
        $team = new Team();
        $user = new User();
        $user->addRole(User::ROLE_USER);
        $team->setTeamLead($user);

        $project = new Project();
        $customer = new Customer();
        $customer->addTeam($team);
        $project->setCustomer($customer);

        $this->assertVote($user, $project, 'edit', VoterInterface::ACCESS_GRANTED);

        $team = new Team();
        $user = new User();
        $user->addRole(User::ROLE_USER);
        $team->addUser($user);

        $project = new Project();
        $customer = new Customer();
        $project->addTeam($team);
        $project->setCustomer($customer);

        $this->assertVote($user, $project, 'edit', VoterInterface::ACCESS_GRANTED);
    }
}
