<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Activity;
use App\Entity\User;
use App\Voter\ActivityVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\ActivityVoter
 */
class ActivityVoterTest extends AbstractVoterTest
{
    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(ActivityVoter::class, $user);

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
            yield [$user, new Activity(), 'view', $result];
            yield [$user, new Activity(), 'edit', $result];
            yield [$user, new Activity(), 'budget', $result];
            yield [$user, new Activity(), 'delete', $result];
        }

        $result = VoterInterface::ACCESS_DENIED;
        foreach ([$user0, $user1, $user2] as $user) {
            yield [$user, new Activity(), 'view', $result];
            yield [$user, new Activity(), 'edit', $result];
            yield [$user, new Activity(), 'budget', $result];
            yield [$user, new Activity(), 'delete', $result];
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ([$user0, $user1, $user2] as $user) {
            yield [$user, new Activity(), 'view_activity', $result];
            yield [$user, new Activity(), 'edit_activity', $result];
            yield [$user, new Activity(), 'budget_activity', $result];
            yield [$user, new Activity(), 'delete_activity', $result];
            yield [$user, new \stdClass(), 'view', $result];
            yield [$user, null, 'edit', $result];
            yield [$user, $user, 'delete', $result];
        }
    }
}
