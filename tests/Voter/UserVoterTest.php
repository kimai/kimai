<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\InvoiceTemplate;
use App\Entity\User;
use App\Voter\UserVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\UserVoter
 */
class UserVoterTest extends AbstractVoterTestCase
{
    /**
     * @dataProvider getTestData
     */
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

    /**
     * @dataProvider getTestDataForAuthType
     */
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
}
