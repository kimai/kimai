<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\ResetPasswordSubscriber;
use App\Tests\Security\TestUserEntity;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\FOSUserEvents;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @covers \App\EventSubscriber\ResetPasswordSubscriber
 */
class ResetPasswordSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $events = ResetPasswordSubscriber::getSubscribedEvents();
        self::assertCount(1, $events);
        $this->assertArrayHasKey(FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE, $events);
        $methodName = $events[FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE][0];
        $this->assertTrue(method_exists(ResetPasswordSubscriber::class, $methodName));
    }

    /**
     * @group legacy
     */
    public function testUnknownUserTypeIsIgnored()
    {
        $user = new TestUserEntity();
        $user->setUsername('foo@bar');

        $request = $this->createMock(Request::class);
        $event = new GetResponseNullableUserEvent($user, $request);

        $sut = new ResetPasswordSubscriber();
        $sut->onInitializeResetPassword($event);

        self::assertNull($event->getResponse());
    }

    public function testInternalAuthTypeIsIgnored()
    {
        $user = new User();
        $user->setUsername('foo@bar');

        $request = $this->createMock(Request::class);
        $event = new GetResponseNullableUserEvent($user, $request);

        $sut = new ResetPasswordSubscriber();
        $sut->onInitializeResetPassword($event);

        self::assertNull($event->getResponse());
    }

    /**
     * @dataProvider getAuthTypeData
     */
    public function testNonInternalAuthTypeThrowsAccessDeniedException(string $authType)
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage(sprintf('The user "foo@bar" tried to reset the password, but it is registered as "%s" auth-type.', $authType));

        $user = new User();
        $user->setUsername('foo@bar');
        $user->setAuth($authType);

        $request = $this->createMock(Request::class);
        $event = new GetResponseNullableUserEvent($user, $request);

        $sut = new ResetPasswordSubscriber();
        $sut->onInitializeResetPassword($event);
    }

    public function getAuthTypeData()
    {
        return [
            [User::AUTH_SAML],
            [User::AUTH_LDAP],
        ];
    }
}
