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
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * @covers \App\EventSubscriber\LastLoginSubscriber
 */
class LastLoginSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $events = LastLoginSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(UserInteractiveLoginEvent::class, $events);
        $methodName = $events[UserInteractiveLoginEvent::class];
        $this->assertTrue(method_exists(LastLoginSubscriber::class, $methodName));

        $this->assertArrayHasKey(SecurityEvents::INTERACTIVE_LOGIN, $events);
        $methodName = $events[SecurityEvents::INTERACTIVE_LOGIN];
        $this->assertTrue(method_exists(LastLoginSubscriber::class, $methodName));
    }

    public function testOnImplicitLogin()
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

    public function testOnSecurityInteractiveLoginWithUser()
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('saveUser');

        $sut = new LastLoginSubscriber($repository);

        $user = new User();
        self::assertNull($user->getLastLogin());

        $event = new InteractiveLoginEvent(new Request(), new UsernamePasswordToken($user, [], 'sdf'));
        $sut->onSecurityInteractiveLogin($event);
        self::assertNotNull($user->getLastLogin());

        $user = new User();
        self::assertNull($user->getLastLogin());

        $event = new InteractiveLoginEvent(new Request(), new UsernamePasswordToken('foo', 'bar', 'sdf'));
        $sut->onSecurityInteractiveLogin($event);
        self::assertNull($user->getLastLogin());
    }
}
