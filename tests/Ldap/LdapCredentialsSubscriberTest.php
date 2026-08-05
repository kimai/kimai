<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Entity\User;
use App\Ldap\LdapBadge;
use App\Ldap\LdapCredentialsSubscriber;
use App\Ldap\LdapManager;
use App\User\UserService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

#[CoversClass(LdapCredentialsSubscriber::class)]
class LdapCredentialsSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = LdapCredentialsSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(CheckPassportEvent::class, $events);
    }

    private function createEvent(User $user, bool $withLdapBadge = true): CheckPassportEvent
    {
        $badges = [new PasswordCredentials('foo-secret')];
        if ($withLdapBadge) {
            $badges[] = new LdapBadge();
        }

        $passport = new Passport(new UserBadge($user->getUserIdentifier(), fn () => $user), $badges[0], \array_slice($badges, 1));

        return new CheckPassportEvent($this->createMock(AuthenticatorInterface::class), $passport);
    }

    private function createLdapManager(bool $bind = true): LdapManager
    {
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['bind', 'updateUser'])->getMock();
        $manager->method('bind')->willReturn($bind);

        return $manager;
    }

    public function testNewUserIsSaved(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->onlyMethods(['saveUser'])->getMock();
        $userService->expects($this->once())->method('saveUser')->with($user)->willReturn($user);

        $sut = new LdapCredentialsSubscriber($this->createLdapManager(), $userService);
        $sut->onCheckPassport($this->createEvent($user));

        // a plain password is required by the validator, when creating a new user
        self::assertNotEmpty($user->getPlainPassword());
    }

    public function testExistingUserIsSaved(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(13);
        $user->method('getUserIdentifier')->willReturn('foobar');
        $user->method('isLdapUser')->willReturn(true);
        $user->expects($this->never())->method('setPlainPassword');

        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->onlyMethods(['saveUser'])->getMock();
        $userService->expects($this->once())->method('saveUser')->with($user)->willReturn($user);

        $sut = new LdapCredentialsSubscriber($this->createLdapManager(), $userService);
        $sut->onCheckPassport($this->createEvent($user));
    }

    public function testFailingSaveThrowsException(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Failed creating or updating user "foobar": Duplicate username');

        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->onlyMethods(['saveUser'])->getMock();
        $userService->expects($this->once())->method('saveUser')->willThrowException(new \RuntimeException('Duplicate username'));

        $sut = new LdapCredentialsSubscriber($this->createLdapManager(), $userService);
        $sut->onCheckPassport($this->createEvent($user));
    }

    public function testUserIsNotSavedWithoutLdapBadge(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');

        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->onlyMethods(['saveUser'])->getMock();
        $userService->expects($this->never())->method('saveUser');

        $sut = new LdapCredentialsSubscriber($this->createLdapManager(), $userService);
        $sut->onCheckPassport($this->createEvent($user, false));
    }

    public function testInternalUserIsNotSavedOnFailedBind(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        self::assertTrue($user->isInternalUser());

        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->onlyMethods(['saveUser'])->getMock();
        $userService->expects($this->never())->method('saveUser');

        $sut = new LdapCredentialsSubscriber($this->createLdapManager(false), $userService);
        $sut->onCheckPassport($this->createEvent($user));
    }

    public function testLdapUserIsNotSavedOnFailedBind(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The presented password is invalid.');

        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->onlyMethods(['saveUser'])->getMock();
        $userService->expects($this->never())->method('saveUser');

        $sut = new LdapCredentialsSubscriber($this->createLdapManager(false), $userService);
        $sut->onCheckPassport($this->createEvent($user));
    }
}
