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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(TeamVoter::class)]
class TeamVoterTest extends AbstractVoterTestCase
{
    #[DataProvider('getTestData')]
    public function testVote(User $user, mixed $subject, string $attribute, int $result): void
    {
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = $this->getVoter(TeamVoter::class);

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    public static function getTestData(): iterable
    {
        $user0 = self::getUser(0, null);
        $user1 = self::getUser(1, User::ROLE_USER);
        $user2 = self::getUser(2, User::ROLE_TEAMLEAD);
        $user3 = self::getUser(3, User::ROLE_ADMIN);
        $user4 = self::getUser(4, User::ROLE_SUPER_ADMIN);

        $team = new Team('foo');

        $abstain = VoterInterface::ACCESS_ABSTAIN;

        $allTeamPerms = ['view_team', 'create_team', 'edit_team', 'delete_team'];

        foreach ($allTeamPerms as $fullPerm) {
            yield [$user0, [], $fullPerm, $abstain];
            yield [$user0, new \stdClass(), $fullPerm, $abstain];
            yield [$user0, $team, $fullPerm, $abstain];
            yield [$user1, $team, $fullPerm, $abstain];
            yield [$user2, $team, $fullPerm, $abstain];
            yield [$user3, $team, $fullPerm, $abstain];
            yield [$user4, $team, $fullPerm, $abstain];
        }

        $denied = VoterInterface::ACCESS_DENIED;

        yield [$user0, $team, 'view', $denied];
        yield [$user0, $team, 'edit', $denied];
        yield [$user0, $team, 'delete', $denied];

        yield [$user1, $team, 'view', $denied];
        yield [$user1, $team, 'edit', $denied];
        yield [$user1, $team, 'delete', $denied];

        yield [$user2, $team, 'view', $denied];
        yield [$user2, $team, 'edit', $denied];
        yield [$user2, $team, 'delete', $denied];

        $granted = VoterInterface::ACCESS_GRANTED;

        yield [$user3, $team, 'view', $granted];
        yield [$user3, $team, 'edit', $granted];
        yield [$user3, $team, 'delete', $granted];

        yield [$user4, $team, 'view', $granted];
        yield [$user4, $team, 'edit', $granted];
        yield [$user4, $team, 'delete', $granted];
    }

    /**
     * Simulates an installation that grants the team permissions to teamleads: access is limited to
     * the teams they lead, plain membership is not enough.
     */
    #[DataProvider('getTeamleadTestData')]
    public function testVoteForTeamleadWithTeamPermissions(User $user, Team $team, string $attribute, int $result): void
    {
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = new TeamVoter($this->getRolePermissionManager([
            'ROLE_TEAMLEAD' => ['view_team', 'edit_team', 'delete_team'],
        ], true));

        self::assertEquals($result, $sut->vote($token, $team, [$attribute]));
    }

    public static function getTeamleadTestData(): iterable
    {
        $teamlead = self::getUser(1, User::ROLE_TEAMLEAD);
        $member = self::getUser(2, User::ROLE_TEAMLEAD);
        $outsider = self::getUser(3, User::ROLE_TEAMLEAD);

        $team = new Team('foo');
        $team->addTeamlead($teamlead);
        $team->addUser($member);

        $denied = VoterInterface::ACCESS_DENIED;
        $granted = VoterInterface::ACCESS_GRANTED;

        foreach (['view', 'edit', 'delete'] as $attribute) {
            yield [$teamlead, $team, $attribute, $granted];
            yield [$member, $team, $attribute, $denied];
            yield [$outsider, $team, $attribute, $denied];
        }
    }
}
