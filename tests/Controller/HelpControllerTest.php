<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

/**
 * @group integration
 */
class HelpControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/help/');
    }

    public function testHelpStartPage()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/help/users'); // TODO remove "users" when the the file /var/docs/README.md is available

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<a href="/en/help/README">', $client->getResponse()->getContent());
    }
}
