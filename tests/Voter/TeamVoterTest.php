<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Team;
use App\Entity\User;
use App\Voter\TeamVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\TeamVoter
 */
class TeamVoterTest extends AbstractVoterTest
{
    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(TeamVoter::class, $user);

        $this->assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    public function getTestData()
    {
        $user0 = $this->getUser(0, null);
        $user1 = $this->getUser(1, User::ROLE_USER);
        $user2 = $this->getUser(2, User::ROLE_TEAMLEAD);
        $user3 = $this->getUser(3, User::ROLE_ADMIN);
        $user4 = $this->getUser(4, User::ROLE_SUPER_ADMIN);

        $team = new Team();

        $result = VoterInterface::ACCESS_ABSTAIN;

        $allTeamPerms = ['view_team', 'create_team', 'edit_team', 'delete_team'];

        foreach ($allTeamPerms as $fullPerm) {
            yield [$user0, [], $fullPerm, $result];
            yield [$user0, new \stdClass(), $fullPerm, $result];
            yield [$user0, $team, $fullPerm, $result];
            yield [$user1, $team, $fullPerm, $result];
            yield [$user2, $team, $fullPerm, $result];
            yield [$user3, $team, $fullPerm, $result];
            yield [$user4, $team, $fullPerm, $result];
        }

        $result = VoterInterface::ACCESS_DENIED;

        yield [$user0, $team, 'view', $result];
        yield [$user0, $team, 'edit', $result];
        yield [$user0, $team, 'delete', $result];

        yield [$user1, $team, 'view', $result];
        yield [$user1, $team, 'edit', $result];
        yield [$user1, $team, 'delete', $result];

        yield [$user2, $team, 'view', $result];
        yield [$user2, $team, 'edit', $result];
        yield [$user2, $team, 'delete', $result];

        $result = VoterInterface::ACCESS_GRANTED;

        yield [$user3, $team, 'view', $result];
        yield [$user3, $team, 'edit', $result];
        yield [$user3, $team, 'delete', $result];

        yield [$user4, $team, 'view', $result];
        yield [$user4, $team, 'edit', $result];
        yield [$user4, $team, 'delete', $result];
    }
}
