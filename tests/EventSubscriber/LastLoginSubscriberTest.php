<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Event\UserInteractiveLoginEvent;
use App\EventSubscriber\LastLoginSubscriber;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[CoversClass(LastLoginSubscriber::class)]
class LastLoginSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = LastLoginSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(UserInteractiveLoginEvent::class, $events);
        $methodName = $events[UserInteractiveLoginEvent::class];
        self::assertIsString($methodName);
        self::assertTrue(method_exists(LastLoginSubscriber::class, $methodName));

        self::assertArrayHasKey(LoginSuccessEvent::class, $events);
        $methodName = $events[LoginSuccessEvent::class];
        self::assertIsString($methodName);
        self::assertTrue(method_exists(LastLoginSubscriber::class, $methodName));
    }

    public function testOnImplicitLogin(): void
    {
        $user = new User();

        $repository = $this->createMock(UserRepository::class);
        // the user entity is no longer modified directly, to prevent excessive writes and
        // Doctrine changeset computes (e.g. on each API request); the "last_login" field is
        // updated by the repository and only becomes visible on subsequent requests
        $repository->expects($this->once())->method('updateLastLogin')->with($user);
        $repository->expects($this->never())->method('saveUser');

        $sut = new LastLoginSubscriber($repository);

        self::assertNull($user->getLastLogin());

        $event = new UserInteractiveLoginEvent($user);
        $sut->onImplicitLogin($event);

        // the in-memory entity stays untouched, the field is only persisted via the repository
        self::assertNull($user->getLastLogin());
    }

    public function testOnLoginSuccessWithUser(): void
    {
        $user = new User();

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('updateLastLogin')->with($user);
        $repository->expects($this->never())->method('saveUser');

        $sut = new LastLoginSubscriber($repository);

        self::assertNull($user->getLastLogin());
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($user);
        $event = new LoginSuccessEvent($authenticator, $passport, new UsernamePasswordToken($user, 'sdf'), new Request(), null, 'xyz');
        $sut->onFormLogin($event);

        self::assertNull($user->getLastLogin());
    }

    public function testOnLoginSuccessWithNonUser(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('updateLastLogin');
        $repository->expects($this->never())->method('saveUser');

        $sut = new LastLoginSubscriber($repository);

        $nonUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($nonUser);
        $event = new LoginSuccessEvent($authenticator, $passport, new UsernamePasswordToken($nonUser, 'sdf'), new Request(), null, 'xyz');
        $sut->onFormLogin($event);
    }
}
