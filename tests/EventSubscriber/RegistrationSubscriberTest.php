<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\RegistrationSubscriber;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \App\EventSubscriber\RegistrationSubscriber
 */
class RegistrationSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $events = RegistrationSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(FOSUserEvents::REGISTRATION_SUCCESS, $events);
        $methodName = $events[FOSUserEvents::REGISTRATION_SUCCESS][0];
        $this->assertTrue(method_exists(RegistrationSubscriber::class, $methodName));

        $this->assertArrayHasKey(FOSUserEvents::RESETTING_RESET_SUCCESS, $events);
        $methodName = $events[FOSUserEvents::RESETTING_RESET_SUCCESS][0];
        $this->assertTrue(method_exists(RegistrationSubscriber::class, $methodName));
    }

    /**
     * @dataProvider getTestData
     */
    public function testRoleAssignmentForNewUser(array $existingUsers, $expectedRoles)
    {
        $user = new User();
        $user->setAlias('foo');
        $this->assertEquals([User::ROLE_USER], $user->getRoles());

        $userManager = $this->getMockBuilder(UserManagerInterface::class)->getMock();
        $userManager->method('findUsers')->willReturn($existingUsers);

        $form = $this->getMockBuilder(FormInterface::class)->getMock();
        $form->method('getData')->willReturn($user);

        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getLocale')->willReturn('ru');

        $event = new FormEvent($form, $request);

        $sut = new RegistrationSubscriber($userManager, $this->createMock(UrlGeneratorInterface::class));
        $sut->onRegistrationSuccess($event);

        $this->assertEquals($expectedRoles, $user->getRoles());
        $this->assertEquals('ru', $user->getLanguage());
    }

    public function getTestData()
    {
        return [
            // NewFirstUserGetsSuperAdminRole
            [[], [User::ROLE_SUPER_ADMIN, User::ROLE_USER]],
            // NewUserGetUserRole
            [[new User()], [User::ROLE_USER]],
        ];
    }

    public function testResetting()
    {
        $userManager = $this->createMock(UserManagerInterface::class);
        $form = $this->createMock(FormInterface::class);
        $request = $this->createMock(Request::class);
        $router = $this->createMock(UrlGeneratorInterface::class);

        $router->expects($this->any())->method('generate')->willReturnArgument(0);
        $request->expects($this->any())->method('getLocale')->willReturn('ru');

        $event = new FormEvent($form, $request);
        self::assertNull($event->getResponse());

        $sut = new RegistrationSubscriber($userManager, $router);
        $sut->onResettingSuccess($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertEquals('my_profile', $response->getTargetUrl());
    }
}
