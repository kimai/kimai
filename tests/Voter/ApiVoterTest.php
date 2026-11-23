<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\User;
use App\Voter\ApiVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(ApiVoter::class)]
class ApiVoterTest extends AbstractVoterTestCase
{
    private function createApiVoter(bool $twoFactorInProgress = false, array $rolePermissions = []): ApiVoter
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(
            static fn (string $attribute): bool => $attribute === 'IS_AUTHENTICATED_2FA_IN_PROGRESS' && $twoFactorInProgress
        );

        $permissionManager = $rolePermissions === []
            ? $this->getRolePermissionManager()
            : $this->getRolePermissionManager($rolePermissions, true);

        return new ApiVoter($permissionManager, $checker);
    }

    /**
     * Regression test for the 2FA-bypass security advisory: the session cookie
     * issued after the password step (which still carries a TwoFactorToken) must
     * not grant access to any #[IsGranted('API')] endpoint.
     */
    public function testTwoFactorTokenIsDenied(): void
    {
        $user = self::getUser(1, User::ROLE_USER);
        $inner = new UsernamePasswordToken($user, 'secured_area', $user->getRoles());
        $token = new TwoFactorToken($inner, null, 'secured_area', ['totp']);

        self::assertInstanceOf(TwoFactorTokenInterface::class, $token);
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->createApiVoter()->vote($token, null, ['API'])
        );
    }

    /**
     * Defense-in-depth branch: even if a future Scheb release stopped using a
     * TwoFactorTokenInterface, the IS_AUTHENTICATED_2FA_IN_PROGRESS role check
     * must still deny.
     */
    public function testTwoFactorInProgressFromAuthCheckerIsDenied(): void
    {
        $user = self::getUser(1, User::ROLE_USER);
        $token = new UsernamePasswordToken($user, 'secured_area', $user->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->createApiVoter(twoFactorInProgress: true)->vote($token, null, ['API'])
        );
    }

    /**
     * Regression test for the security.yaml change to IS_AUTHENTICATED_REMEMBERED:
     * a remember_me-backed session (the frontend uses the API this way because
     * always_remember_me is enabled) must still pass the voter.
     */
    public function testRememberMeSessionIsGranted(): void
    {
        $user = self::getUser(1, User::ROLE_USER);
        $token = new RememberMeToken($user, 'secured_area', 'secret');

        self::assertNotInstanceOf(TwoFactorTokenInterface::class, $token); // @phpstan-ignore staticMethod.alreadyNarrowedType
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->createApiVoter()->vote($token, null, ['API'])
        );
    }

    public function testRegularSessionIsGranted(): void
    {
        $user = self::getUser(1, User::ROLE_USER);
        $token = new UsernamePasswordToken($user, 'secured_area', $user->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->createApiVoter()->vote($token, null, ['API'])
        );
    }

    public function testApiTokenWithoutPermissionIsDenied(): void
    {
        $user = self::getUser(1, User::ROLE_USER);
        $token = new UsernamePasswordToken($user, 'api', $user->getRoles());
        $token->setAttribute('api-token', true);

        // ROLE_USER does not carry 'api_access' in the default permission map
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->createApiVoter()->vote($token, null, ['API'])
        );
    }

    public function testApiTokenWithPermissionIsGranted(): void
    {
        $user = self::getUser(1, User::ROLE_USER);
        $token = new UsernamePasswordToken($user, 'api', $user->getRoles());
        $token->setAttribute('api-token', true);

        $voter = $this->createApiVoter(rolePermissions: ['ROLE_USER' => ['api_access']]);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($token, null, ['API'])
        );
    }

    public function testNonUserSubjectIsDenied(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->createApiVoter()->vote($token, null, ['API'])
        );
    }
}
