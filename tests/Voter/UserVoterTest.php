<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\InvoiceTemplate;
use App\Entity\Team;
use App\Entity\User;
use App\Voter\UserVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(UserVoter::class)]
class UserVoterTest extends AbstractVoterTestCase
{
    #[DataProvider('getTestData')]
    public function testVote(User $user, $subject, $attribute, $result): void
    {
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = $this->getVoter(UserVoter::class);

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    public static function getTestData()
    {
        $user0 = self::getUser(0, null);
        $user1 = self::getUser(1, User::ROLE_USER);
        $user2 = self::getUser(2, User::ROLE_TEAMLEAD);
        $user3 = self::getUser(3, User::ROLE_ADMIN);
        $user4 = self::getUser(4, User::ROLE_SUPER_ADMIN);

        $result = VoterInterface::ACCESS_GRANTED;
        foreach ([$user1, $user2, $user3] as $user) {
            yield [$user4, $user, 'view', $result];
            yield [$user4, $user, 'edit', $result];
            yield [$user4, $user, 'password', $result];
            yield [$user4, $user, 'roles', $result];
            yield [$user4, $user, 'preferences', $result];
            yield [$user4, $user, 'api-token', $result];
        }

        foreach ([$user1, $user2, $user3] as $user) {
            yield [$user4, $user, 'delete', $result];
        }

        foreach ([$user1, $user2, $user3, $user4] as $user) {
            yield [$user, $user, 'view', $result];
            yield [$user, $user, 'edit', $result];
            yield [$user, $user, 'password', $result];
            yield [$user, $user, 'preferences', $result];
            yield [$user, $user, 'api-token', $result];
        }

        $result = VoterInterface::ACCESS_DENIED;
        foreach ([$user0, $user1, $user2] as $user) {
            yield [$user, $user, 'roles', $result];
            yield [$user3, $user, 'roles', $result];
            yield [$user, $user3, 'view', $result];
            yield [$user, $user3, 'edit', $result];
            yield [$user, $user3, 'delete', $result];
            yield [$user, $user4, 'view', $result];
            yield [$user, $user4, 'edit', $result];
            yield [$user, $user4, 'delete', $result];
            yield [$user, $user4, 'hourly-rate', $result];
            yield [$user, $user4, 'hourly-rate', $result];
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ([$user0, $user1, $user2] as $user) {
            yield [$user, new InvoiceTemplate(), 'view', $result];
            yield [$user, new InvoiceTemplate(), 'edit', $result];
            yield [$user, new InvoiceTemplate(), 'delete', $result];
            yield [$user, new \stdClass(), 'view', $result];
            yield [$user, null, 'edit', $result];
            yield [$user, null, 'delete', $result];
        }
    }

    #[DataProvider('getTestDataForAuthType')]
    public function testPasswordIsDeniedForNonInternalUser(string $authType, int $result): void
    {
        $user = new User();
        $user->setUserIdentifier('admin');
        $user->addRole('ROLE_SUPER_ADMIN');

        $subject = new User();
        $subject->setUserIdentifier('foo');
        $subject->addRole('ROLE_USER');
        $subject->setAuth($authType);

        $this->testVote($user, $subject, 'password', $result);
    }

    public static function getTestDataForAuthType()
    {
        return [
          [User::AUTH_LDAP, VoterInterface::ACCESS_DENIED],
          [User::AUTH_INTERNAL, VoterInterface::ACCESS_GRANTED],
          [User::AUTH_OIDC, VoterInterface::ACCESS_DENIED],
          [User::AUTH_SAML, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testViewTeamMember(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);
        $user = new User();
        $token = new UsernamePasswordToken($user, 'bar', $user->getRoles());
        $sut = $this->getVoter(UserVoter::class);

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $sut->vote($token, $user, ['view_team_member']));
        self::assertEquals(VoterInterface::ACCESS_DENIED, $sut->vote($token, $userMock, ['view_team_member']));
    }

    /**
     * Even with the "<attribute>_other_profile" role permission, access to another user's
     * profile must additionally pass the team-membership check in
     * RolePermissionManager::checkUserAccess().
     */
    public function testOtherProfileRequiresTeamRelation(): void
    {
        $teamlead = self::getUser(10, User::ROLE_TEAMLEAD);
        $teamlead->setEnabled(true);
        $foreignUser = self::getUser(11, User::ROLE_USER);
        $foreignUser->setEnabled(true);

        // give the foreign user a team that the teamlead is NOT part of,
        // so the special "subject has no teams" fallback does not kick in
        $team = new Team('foreign team');
        $foreignUser->addTeam($team);

        $permissions = [
            'ROLE_TEAMLEAD' => ['view_other_profile', 'edit_other_profile'],
        ];
        $rpm = $this->getRolePermissionManager($permissions, true);
        $voter = new UserVoter($rpm);

        $token = new UsernamePasswordToken($teamlead, 'bar', $teamlead->getRoles());

        // the role permission "view_other_profile" exists, but the team relation is missing
        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $foreignUser, ['view']));
        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $foreignUser, ['edit']));
    }

    /**
     * Same setup as above, but the current user IS teamlead of one of the subject's
     * teams — access should then be granted.
     */
    public function testOtherProfileGrantedWhenUserIsTeamleadOfSubject(): void
    {
        $teamlead = self::getUser(20, User::ROLE_TEAMLEAD);
        $teamlead->setEnabled(true);
        $member = self::getUser(21, User::ROLE_USER);
        $member->setEnabled(true);

        $team = new Team('shared team');
        $team->addUser($member);
        $team->addTeamlead($teamlead);

        $permissions = [
            'ROLE_TEAMLEAD' => ['view_other_profile', 'edit_other_profile'],
        ];
        $rpm = $this->getRolePermissionManager($permissions, true);
        $voter = new UserVoter($rpm);

        $token = new UsernamePasswordToken($teamlead, 'bar', $teamlead->getRoles());

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $member, ['view']));
        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $member, ['edit']));
    }

    /**
     * Verify that disabled profiles can still be edited (e.g. to reactivate them or check historic data).
     */
    public function testOtherProfileAllowedForDisabledSubject(): void
    {
        $teamlead = self::getUser(30, User::ROLE_TEAMLEAD);
        $teamlead->setEnabled(true);
        $member = self::getUser(31, User::ROLE_USER);
        $member->setEnabled(false);

        $team = new Team('shared team');
        $team->addUser($member);
        $team->addTeamlead($teamlead);

        $permissions = [
            'ROLE_TEAMLEAD' => ['view_other_profile', 'edit_other_profile'],
        ];
        $rpm = $this->getRolePermissionManager($permissions, true);
        $voter = new UserVoter($rpm);

        $token = new UsernamePasswordToken($teamlead, 'bar', $teamlead->getRoles());

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $member, ['view']));
        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $member, ['edit']));
    }

    /**
     * Special case in checkUserAccess(): if the subject has no teams at all and the
     * current user is a teamlead/admin, access is granted (small-installation case).
     */
    public function testOtherProfileGrantedForTeamlessSubjectWhenUserIsTeamlead(): void
    {
        $teamlead = self::getUser(40, User::ROLE_TEAMLEAD);
        $teamlead->setEnabled(true);
        $lonelyUser = self::getUser(41, User::ROLE_USER);
        $lonelyUser->setEnabled(true);

        $permissions = [
            'ROLE_TEAMLEAD' => ['view_other_profile', 'edit_other_profile'],
        ];
        $rpm = $this->getRolePermissionManager($permissions, true);
        $voter = new UserVoter($rpm);

        $token = new UsernamePasswordToken($teamlead, 'bar', $teamlead->getRoles());

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $lonelyUser, ['view']));
        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $lonelyUser, ['edit']));
    }

    /**
     * Without the role permission "<attribute>_other_profile" the voter must deny,
     * regardless of any team relation between user and subject.
     */
    public function testOtherProfileDeniedWithoutRolePermissionEvenWithTeamRelation(): void
    {
        $teamlead = self::getUser(50, User::ROLE_TEAMLEAD);
        $teamlead->setEnabled(true);
        $member = self::getUser(51, User::ROLE_USER);
        $member->setEnabled(true);

        $team = new Team('shared team');
        $team->addUser($member);
        $team->addTeamlead($teamlead);

        // no "view_other_profile" permission for ROLE_TEAMLEAD
        $permissions = [
            'ROLE_TEAMLEAD' => ['view_own_profile'],
        ];
        $rpm = $this->getRolePermissionManager($permissions, true);
        $voter = new UserVoter($rpm);

        $token = new UsernamePasswordToken($teamlead, 'bar', $teamlead->getRoles());

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $member, ['view']));
    }
}
