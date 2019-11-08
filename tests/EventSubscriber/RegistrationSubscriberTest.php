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
use Symfony\Component\HttpFoundation\Request;

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

        $event = new FormEvent($form, $request);

        $sut = new RegistrationSubscriber($userManager);
        $sut->onRegistrationSuccess($event);

        $this->assertEquals($expectedRoles, $user->getRoles());
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
}
