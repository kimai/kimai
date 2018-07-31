<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;

/**
 * @coversDefaultClass \App\Controller\InvoiceController
 * @group integration
 */
class ProfileControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/profile/' . UserFixtures::USERNAME_USER);
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $tabs = $client->getCrawler()->filter('div.nav-tabs-custom ul.nav-tabs li');
        $this->assertEquals(4, $tabs->count());
        $expectedTabs = ['#charts', '#settings', '#password', '#preferences'];
        $foundTabs = [];
        foreach ($tabs->filter('a') as $tab) {
            $name = $tab->getAttribute('href');
            if (in_array($name, $expectedTabs)) {
                $foundTabs[] = $name;
            }
        }
        $this->assertEmpty(array_diff($expectedTabs, $foundTabs));
    }

    public function testIndexActionWithDifferentUsername()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/profile/' . UserFixtures::USERNAME_TEAMLEAD);
        $this->assertFalse($client->getResponse()->isSuccessful());
    }
}
