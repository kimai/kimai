<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;

/**
 * @coversDefaultClass \App\Controller\DashboardController
 * @group integration
 */
class DashboardControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/dashboard/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/dashboard/');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertMainContentClass($client, 'dashboard');
    }

    public function testIndexActionForAdmin()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/dashboard/');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertMainContentClass($client, 'dashboard');
    }

    /**
     * This is not a test for the dashbaord, but for the general layout
     */
    public function testUserMenuIsAvailable()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->request($client, '/dashboard/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();

        $this->assertContains('<li class="dropdown user-menu">', $content);
        $this->assertContains('<a href="/en/profile/' . $user->getUsername() . '">', $content);
        $this->assertContains('<a href="/en/profile/' . $user->getUsername() . '/prefs">', $content);
        $this->assertContains('<a href="/en/logout">', $content);
    }
}
