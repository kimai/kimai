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
use App\Entity\User;
use App\Voter\ProjectVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\ProjectVoter
 */
class ProjectVoterTest extends AbstractVoterTest
{
    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(ProjectVoter::class, $user);

        if ($subject instanceof Project) {
            $subject->setCustomer(new Customer());
        }

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
            yield [$user, new Project(), 'view', $result];
            yield [$user, new Project(), 'edit', $result];
            yield [$user, new Project(), 'budget', $result];
            yield [$user, new Project(), 'delete', $result];
        }

        $result = VoterInterface::ACCESS_DENIED;
        foreach ([$user0, $user1, $user2] as $user) {
            yield [$user, new Project(), 'view', $result];
            yield [$user, new Project(), 'edit', $result];
            yield [$user, new Project(), 'budget', $result];
            yield [$user, new Project(), 'delete', $result];
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ([$user0, $user1, $user2] as $user) {
            yield [$user, new Project(), 'create_project', $result];
            yield [$user, new Project(), 'view_project', $result];
            yield [$user, new Project(), 'edit_project', $result];
            yield [$user, new Project(), 'budget_project', $result];
            yield [$user, new Project(), 'delete_project', $result];
            yield [$user, new \stdClass(), 'view', $result];
            yield [$user, null, 'edit', $result];
            yield [$user, $user, 'delete', $result];
        }
    }
}
