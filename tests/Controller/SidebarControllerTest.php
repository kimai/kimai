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
 * @coversDefaultClass \App\Controller\SidebarController
 * @group integration
 */
class SidebarControllerTest extends ControllerBaseTest
{
    public function testSettingsAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->request($client, '/sidebar/settings');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();

        $this->assertContains('<ul class="control-sidebar-menu">', $content);
        $this->assertContains('<a href="/en/profile/' . $user->getUsername() . '">', $content);
        $this->assertContains('<a href="/en/profile/' . $user->getUsername() . '/edit">', $content);
        $this->assertContains('<a href="/en/profile/' . $user->getUsername() . '/prefs">', $content);
        $this->assertContains('<a href="/en/help/">', $content);
        $this->assertContains('<a href="/en/logout">', $content);
    }
}
