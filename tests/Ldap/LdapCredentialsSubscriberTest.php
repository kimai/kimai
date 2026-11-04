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
use App\User\ExternalUserSanitizer;
use App\User\UserService;
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    private function createUserService(): UserService&MockObject
    {
        return $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->onlyMethods(['prepareNewUser', 'saveUser'])->getMock();
    }

    private function createSut(LdapManager $ldapManager, UserService $userService, ?LoggerInterface $logger = null): LdapCredentialsSubscriber
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validateProperty')->willReturn(new ConstraintViolationList());
        $sanitizer = new ExternalUserSanitizer($validator, $this->createMock(LoggerInterface::class));

        return new LdapCredentialsSubscriber($ldapManager, $userService, $sanitizer, $logger ?? $this->createMock(LoggerInterface::class));
    }

    public function testNewUserIsPreparedAndSaved(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $userService = $this->createUserService();
        $userService->expects($this->once())->method('prepareNewUser')->with($user)->willReturn($user);
        $userService->expects($this->once())->method('saveUser')->with($user)->willReturn($user);

        $sut = $this->createSut($this->createLdapManager(), $userService);
        $sut->onCheckPassport($this->createEvent($user));

        // a plain password is required by the validator, when creating a new user
        self::assertNotEmpty($user->getPlainPassword());
    }

    public function testExistingUserIsSavedWithoutDefaults(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(13);
        $user->method('getUserIdentifier')->willReturn('foobar');
        $user->method('isLdapUser')->willReturn(true);
        $user->expects($this->never())->method('setPlainPassword');

        $userService = $this->createUserService();
        $userService->expects($this->never())->method('prepareNewUser');
        $userService->expects($this->once())->method('saveUser')->with($user)->willReturn($user);

        $sut = $this->createSut($this->createLdapManager(), $userService);
        $sut->onCheckPassport($this->createEvent($user));
    }

    public function testDefaultsAreAppliedBeforeTheLdapAttributes(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $calls = [];

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['bind', 'updateUser'])->getMock();
        $manager->method('bind')->willReturn(true);
        $manager->method('updateUser')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'updateUser';
        });

        $userService = $this->createUserService();
        $userService->method('prepareNewUser')->willReturnCallback(function (User $user) use (&$calls) {
            $calls[] = 'prepareNewUser';

            return $user;
        });
        $userService->method('saveUser')->willReturnCallback(function (User $user) use (&$calls) {
            $calls[] = 'saveUser';

            return $user;
        });

        $sut = $this->createSut($manager, $userService);
        $sut->onCheckPassport($this->createEvent($user));

        // the roles and attributes from LDAP must not be overwritten by the system defaults
        self::assertEquals(['prepareNewUser', 'updateUser', 'saveUser'], $calls);
    }

    public function testFailingSaveThrowsException(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Failed creating or updating user "foobar": Duplicate username');

        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $userService = $this->createUserService();
        $userService->expects($this->once())->method('saveUser')->willThrowException(new \RuntimeException('Duplicate username'));

        $sut = $this->createSut($this->createLdapManager(), $userService);
        $sut->onCheckPassport($this->createEvent($user));
    }

    public function testInvalidAvatarIsRemovedBeforeSaving(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);
        // a relative URL, which could have been set by the LDAP attribute mapping
        $user->setAvatar('/images/avatar.png');

        $userService = $this->createUserService();
        $userService->expects($this->once())->method('saveUser')->willReturnArgument(0);

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $sanitizer = new ExternalUserSanitizer($validator, $this->createMock(LoggerInterface::class));

        $sut = new LdapCredentialsSubscriber($this->createLdapManager(), $userService, $sanitizer, $this->createMock(LoggerInterface::class));
        $sut->onCheckPassport($this->createEvent($user));

        self::assertNull($user->getAvatar());
    }

    public function testFailingValidationIsLogged(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value is not a valid URL.', null, [], null, 'avatar', 'foo'),
        ]);

        $userService = $this->createUserService();
        $userService->expects($this->once())->method('saveUser')->willThrowException(new ValidationFailedException($violations));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')
            ->with('Failed creating or updating LDAP user "foobar": avatar: This value is not a valid URL.');

        $sut = $this->createSut($this->createLdapManager(), $userService, $logger);

        try {
            $sut->onCheckPassport($this->createEvent($user));
            self::fail('Expected AuthenticationException was not thrown');
        } catch (AuthenticationException $ex) {
            self::assertEquals('Failed creating or updating user "foobar": avatar: This value is not a valid URL.', $ex->getMessage());
        }
    }

    public function testUserIsNotSavedWithoutLdapBadge(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');

        $userService = $this->createUserService();
        $userService->expects($this->never())->method('saveUser');

        $sut = $this->createSut($this->createLdapManager(), $userService);
        $sut->onCheckPassport($this->createEvent($user, false));
    }

    public function testInternalUserIsNotSavedOnFailedBind(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        self::assertTrue($user->isInternalUser());

        $userService = $this->createUserService();
        $userService->expects($this->never())->method('saveUser');

        $sut = $this->createSut($this->createLdapManager(false), $userService);
        $sut->onCheckPassport($this->createEvent($user));
    }

    public function testLdapUserIsNotSavedOnFailedBind(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The presented password is invalid.');

        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $userService = $this->createUserService();
        $userService->expects($this->never())->method('saveUser');

        $sut = $this->createSut($this->createLdapManager(false), $userService);
        $sut->onCheckPassport($this->createEvent($user));
    }
}
