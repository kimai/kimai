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
class ReportingControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/reporting');
    }

    public function testOverviewPage()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/reporting/');
        $nodes = $client->getCrawler()->filter('section.content div.card');
        $this->assertCount(11, $nodes);
    }

    public function testOverviewPageAsUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/reporting/');
        $nodes = $client->getCrawler()->filter('section.content div.card');
        $this->assertCount(3, $nodes);
    }
}
