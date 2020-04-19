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
 * @group integration
 */
class AboutControllerTest extends ControllerBaseTest
{
    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/about');

        $result = $client->getCrawler()->filter('ul.nav.nav-stacked li a');
        $this->assertEquals(4, \count($result));

        $result = $client->getCrawler()->filter('div.box-body pre');
        $this->assertEquals(1, \count($result));
        $this->assertStringContainsString('MIT License', $result->text(null, true));
    }
}
