<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\DataFixtures\TagFixtures;

/**
 * @covers \App\Controller\TagController
 * @covers \App\Controller\AbstractController
 * @group integration
 */
class TagControllerTest extends ControllerBaseTest
{
    public function setUp()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $tagList = ['Test', 'Administration', 'Support', '#2018-001', '#2018-002', '#2018-003', 'Development',
            'Marketing', 'First Level Support', 'Bug Fixing'];

        $fixture = new TagFixtures();
        $fixture->setTagArray($tagList);
        $this->importFixture($em, $fixture);
    }

    public function testDebugIsSecure()
    {
        $this->assertUrlIsSecured('/admin/tags/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/admin/tags/');

        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_admin_tags', 10);
    }
}
