<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\User;
use App\Voter\ImpersonateVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(ImpersonateVoter::class)]
class ImpersonateVoterTest extends AbstractVoterTestCase
{
    #[DataProvider('getVoteData')]
    public function testVote(User $authenticatedUser, mixed $subject, string $attribute, int $result): void
    {
        $token = new UsernamePasswordToken($authenticatedUser, 'bar', $authenticatedUser->getRoles());
        $sut = new ImpersonateVoter($this->getRolePermissionManager([
            User::ROLE_ADMIN => ['impersonate_user'],
            User::ROLE_SUPER_ADMIN => ['impersonate_user'],
        ], true));

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    public static function getVoteData(): iterable
    {
        $plainUser = self::getUser(1, User::ROLE_USER);
        $admin = self::getUser(2, User::ROLE_ADMIN);
        $superAdmin = self::getUser(3, User::ROLE_SUPER_ADMIN);
        $target = self::getUser(4, User::ROLE_USER);

        $granted = VoterInterface::ACCESS_GRANTED;
        yield [$superAdmin, $target, 'impersonate_user', $granted];

        $denied = VoterInterface::ACCESS_DENIED;
        yield [$plainUser, $target, 'impersonate_user', $denied];
        yield [$admin, $target, 'impersonate_user', $denied];

        $abstain = VoterInterface::ACCESS_ABSTAIN;
        yield [$superAdmin, null, 'impersonate_user', $abstain];
        yield [$superAdmin, new \stdClass(), 'impersonate_user', $abstain];
        yield [$superAdmin, $target, 'view', $abstain];
        yield [$superAdmin, new \stdClass(), 'view', $abstain];
    }

    public function testVoteDeniesMissingAuthenticatedUser(): void
    {
        $target = self::getUser(2, User::ROLE_USER);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $sut = new ImpersonateVoter($this->getRolePermissionManager([
            User::ROLE_SUPER_ADMIN => ['impersonate_user'],
        ], true));

        self::assertEquals(VoterInterface::ACCESS_DENIED, $sut->vote($token, $target, ['impersonate_user']));
    }

    public function testVoteDeniesSuperAdminWithoutPermission(): void
    {
        $superAdmin = self::getUser(1, User::ROLE_SUPER_ADMIN);
        $target = self::getUser(2, User::ROLE_USER);
        $token = new UsernamePasswordToken($superAdmin, 'bar', $superAdmin->getRoles());
        $sut = new ImpersonateVoter($this->getRolePermissionManager([], true));

        self::assertEquals(VoterInterface::ACCESS_DENIED, $sut->vote($token, $target, ['impersonate_user']));
    }
}
