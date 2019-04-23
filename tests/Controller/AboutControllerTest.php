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
 * @coversDefaultClass \App\Controller\AboutController
 * @group integration
 */
class AboutControllerTest extends ControllerBaseTest
{
    public function testDebugIsSecure()
    {
        $this->assertUrlIsSecured('/about/debug');
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/about/debug');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/about');

        $result = $client->getCrawler()->filter('ul.nav.nav-stacked li a');
        $this->assertEquals(3, count($result));

        $result = $client->getCrawler()->filter('div.box-body pre');
        $this->assertEquals(1, count($result));
        $this->assertContains('MIT License', $result->text());
    }

    public function testDebugAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/about/debug');

        $result = $client->getCrawler()->filter('div.box-body.about_debug');
        $this->assertEquals(1, count($result));

        $content = $result->text();

        $this->assertContains('Actions', $content);
        $this->assertContains('Environment', $content);
        $this->assertContains('PHP', $content);
        $this->assertContains('Server', $content);
    }}
