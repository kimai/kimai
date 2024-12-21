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
use App\Entity\User;
use App\Voter\EntityMultiRoleVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\EntityMultiRoleVoter
 */
class EntityMultiRoleVoterTest extends AbstractVoterTestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $subject, $attribute, $result): void
    {
        $token = new UsernamePasswordToken($user, 'foo', $user->getRoles());
        $sut = $this->getVoter(EntityMultiRoleVoter::class);

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]), 'Failed on permission "' . $attribute . '" for User ' . $user->getUserIdentifier());
    }

    public static function getTestData()
    {
        $user0 = self::getUser(0, null);
        $user1 = self::getUser(1, User::ROLE_USER);
        $user2 = self::getUser(2, User::ROLE_TEAMLEAD);
        $user3 = self::getUser(3, User::ROLE_ADMIN);
        $user4 = self::getUser(4, User::ROLE_SUPER_ADMIN);

        $result = VoterInterface::ACCESS_GRANTED;
        $allPermissions = ['budget_money', 'budget_time', 'budget_any', 'details'];
        $allSubjects = ['project', 'customer', new Project(), new Customer('foo')];

        foreach ($allPermissions as $permission) {
            foreach ($allSubjects as $subject) {
                yield [$user3, $subject, $permission, $result];
                yield [$user4, $subject, $permission, $result];
            }
        }

        $result = VoterInterface::ACCESS_GRANTED;
        $allPermissions = ['budget_money', 'budget_time', 'budget_any'];
        $allSubjects = ['activity', new Activity()];

        foreach ($allPermissions as $permission) {
            foreach ($allSubjects as $subject) {
                yield [$user3, $subject, $permission, $result];
                yield [$user4, $subject, $permission, $result];
            }
        }

        $result = VoterInterface::ACCESS_DENIED;
        yield [$user4, 'activity', 'details', $result]; // there is no details permission for activity

        $result = VoterInterface::ACCESS_ABSTAIN;
        yield [$user0, 'team', 'view', $result];
        yield [$user0, 'team', 'edit', $result];
        yield [$user0, 'team', 'delete', $result];
        yield [$user1, 'team', 'view', $result];
        yield [$user1, 'team', 'edit', $result];
        yield [$user1, 'team', 'delete', $result];
        yield [$user2, 'team', 'view', $result];
        yield [$user2, 'team', 'edit', $result];
        yield [$user2, 'team', 'delete', $result];
        yield [$user3, 'team', 'view', $result];
        yield [$user3, 'team', 'edit', $result];
        yield [$user3, 'team', 'delete', $result];
        yield [$user4, 'team', 'view', $result];
        yield [$user4, 'team', 'edit', $result];
        yield [$user4, 'team', 'delete', $result];
    }
}
