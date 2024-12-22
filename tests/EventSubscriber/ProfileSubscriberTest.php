<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LastLoginSubscriber;
use App\EventSubscriber\ProfileSubscriber;
use App\Utils\ProfileManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @covers \App\EventSubscriber\ProfileSubscriber
 */
class ProfileSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = ProfileSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(LoginSuccessEvent::class, $events);
        $methodName = $events[LoginSuccessEvent::class];
        self::assertTrue(method_exists(LastLoginSubscriber::class, $methodName));
    }

    public function testOnLoginSuccessWithoutProfileSetsDesktop(): void
    {
        $manager = new ProfileManager();
        $sut = new ProfileSubscriber($manager);

        $request = new Request();
        $session = new Session(new MockFileSessionStorage());
        $request->setSession($session);

        self::assertNull($session->get(ProfileManager::SESSION_PROFILE));

        $user = new User();
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($user);
        $event = new LoginSuccessEvent($authenticator, $passport, new UsernamePasswordToken($user, 'sdf'), $request, null, 'xyz');
        $sut->onFormLogin($event);

        self::assertNull($session->get(ProfileManager::SESSION_PROFILE));
        self::assertEquals(ProfileManager::PROFILE_DESKTOP, $manager->getProfileFromSession($session));
    }

    public function testOnLoginSuccessWithProfile(): void
    {
        $manager = new ProfileManager();
        $sut = new ProfileSubscriber($manager);

        $request = new Request();
        $session = new Session(new MockFileSessionStorage());
        $request->setSession($session);

        $request->cookies->set(ProfileManager::COOKIE_PROFILE, 'mobile');

        self::assertNull($session->get(ProfileManager::SESSION_PROFILE));

        $user = new User();
        self::assertNull($user->getLastLogin());
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($user);
        $event = new LoginSuccessEvent($authenticator, $passport, new UsernamePasswordToken($user, 'sdf'), $request, null, 'xyz');
        $sut->onFormLogin($event);

        self::assertNotNull($session->get(ProfileManager::SESSION_PROFILE));
        self::assertEquals(ProfileManager::PROFILE_MOBILE, $session->get(ProfileManager::SESSION_PROFILE));
        self::assertEquals(ProfileManager::PROFILE_MOBILE, $manager->getProfileFromSession($session));
    }

    public static function getInvalidCookies()
    {
        return [
            ['MOBILE'],
            ['mobilE'],
            ['mobile2'],
            ['mobile2'],
            ['foo'],
            ['mobile '],
            [' desktop'],
            ['DESKTOP'],
            // the next two ones are misleading: default value "desktop" will not be set and session value will be removed
            ['desktop'],
            [ProfileManager::PROFILE_DESKTOP],
        ];
    }

    /**
     * @dataProvider getInvalidCookies
     */
    public function testOnLoginSuccessWithInvalidProfile(string $cookieValue): void
    {
        $manager = new ProfileManager();
        $sut = new ProfileSubscriber($manager);

        $request = new Request();
        $session = new Session(new MockFileSessionStorage());
        $request->setSession($session);

        $request->cookies->set(ProfileManager::COOKIE_PROFILE, $cookieValue);

        self::assertNull($session->get(ProfileManager::SESSION_PROFILE));

        $user = new User();
        self::assertNull($user->getLastLogin());
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($user);
        $event = new LoginSuccessEvent($authenticator, $passport, new UsernamePasswordToken($user, 'sdf'), $request, null, 'xyz');
        $sut->onFormLogin($event);

        self::assertNull($session->get(ProfileManager::SESSION_PROFILE));
        self::assertEquals(ProfileManager::PROFILE_DESKTOP, $manager->getProfileFromSession($session));
    }
}
