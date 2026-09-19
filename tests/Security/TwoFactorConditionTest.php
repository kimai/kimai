<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\TwoFactorCondition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

#[CoversClass(TwoFactorCondition::class)]
class TwoFactorConditionTest extends TestCase
{
    private function createUser(string $username): User
    {
        $user = new User();
        $user->setUserIdentifier($username);
        $user->setPassword('foo');

        return $user;
    }

    private function createContext(User $user, string $uri = '/en/login_check'): AuthenticationContextInterface
    {
        $context = $this->createMock(AuthenticationContextInterface::class);
        $context->method('getUser')->willReturn($user);
        $context->method('getRequest')->willReturn(Request::create($uri, 'POST'));

        return $context;
    }

    private function createSut(?TokenInterface $currentToken): TwoFactorCondition
    {
        $storage = new TokenStorage();
        $storage->setToken($currentToken);

        return new TwoFactorCondition($storage);
    }

    public function testApiRequestsNeverRequireTwoFactor(): void
    {
        $sut = $this->createSut(null);
        $context = $this->createContext($this->createUser('victim'), '/api/timesheets');

        self::assertFalse($sut->shouldPerformTwoFactorAuthentication($context));
    }

    public function testFreshLoginRequiresTwoFactor(): void
    {
        $sut = $this->createSut(null);
        $context = $this->createContext($this->createUser('victim'));

        self::assertTrue($sut->shouldPerformTwoFactorAuthentication($context));
    }

    public function testRememberedUserCanUnlockWithoutTwoFactor(): void
    {
        $user = $this->createUser('john');
        $sut = $this->createSut(new RememberMeToken($user, 'secured_area', 'secret'));
        $context = $this->createContext($user);

        self::assertFalse($sut->shouldPerformTwoFactorAuthentication($context));
    }

    public function testRememberedUserIsComparedByIdentifierNotByInstance(): void
    {
        $sut = $this->createSut(new RememberMeToken($this->createUser('john'), 'secured_area', 'secret'));
        // a different object instance for the same user (e.g. re-loaded from the database)
        $context = $this->createContext($this->createUser('john'));

        self::assertFalse($sut->shouldPerformTwoFactorAuthentication($context));
    }

    /**
     * Regression test: a remember-me cookie for one account must never skip the 2FA of another account.
     */
    public function testRememberedUserCannotSkipTwoFactorOfOtherUser(): void
    {
        $sut = $this->createSut(new RememberMeToken($this->createUser('attacker'), 'secured_area', 'secret'));
        $context = $this->createContext($this->createUser('victim'));

        self::assertTrue($sut->shouldPerformTwoFactorAuthentication($context));
    }

    /**
     * Regression test: an existing (fully authenticated) session must never skip the 2FA of another account.
     */
    public function testFullyAuthenticatedUserCannotSkipTwoFactorOfOtherUser(): void
    {
        $sut = $this->createSut(new PostAuthenticationToken($this->createUser('attacker'), 'secured_area', ['ROLE_USER']));
        $context = $this->createContext($this->createUser('victim'));

        self::assertTrue($sut->shouldPerformTwoFactorAuthentication($context));
    }

    /**
     * Only a remember-me token proves that the TOTP challenge was passed before.
     */
    public function testFullyAuthenticatedTokenOfSameUserStillRequiresTwoFactor(): void
    {
        $user = $this->createUser('john');
        $sut = $this->createSut(new UsernamePasswordToken($user, 'secured_area', ['ROLE_USER']));
        $context = $this->createContext($user);

        self::assertTrue($sut->shouldPerformTwoFactorAuthentication($context));
    }
}
