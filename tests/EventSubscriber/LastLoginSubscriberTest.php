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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @covers \App\EventSubscriber\LastLoginSubscriber
 */
class LastLoginSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = LastLoginSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(UserInteractiveLoginEvent::class, $events);
        $methodName = $events[UserInteractiveLoginEvent::class];
        $this->assertTrue(method_exists(LastLoginSubscriber::class, $methodName));

        $this->assertArrayHasKey(LoginSuccessEvent::class, $events);
        $methodName = $events[LoginSuccessEvent::class];
        $this->assertTrue(method_exists(LastLoginSubscriber::class, $methodName));
    }

    public function testOnImplicitLogin(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('saveUser');

        $sut = new LastLoginSubscriber($repository);

        $user = new User();
        self::assertNull($user->getLastLogin());

        $event = new UserInteractiveLoginEvent($user);
        $sut->onImplicitLogin($event);
        self::assertNotNull($user->getLastLogin());
    }

    public function testOnLoginSuccessWithUser(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('saveUser');

        $sut = new LastLoginSubscriber($repository);

        $user = new User();
        self::assertNull($user->getLastLogin());
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($user);
        $event = new LoginSuccessEvent($authenticator, $passport, new UsernamePasswordToken($user, 'sdf'), new Request(), null, 'xyz');
        $sut->onFormLogin($event);
        self::assertNotNull($user->getLastLogin());
    }
}
