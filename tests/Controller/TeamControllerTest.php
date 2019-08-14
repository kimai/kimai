<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\DataFixtures\TeamFixtures;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @group integration
 */
class TeamControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/teams/');
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/admin/teams/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/teams/');
        $this->assertPageActions($client, ['create' => $this->createUrl('/admin/teams/create')]);
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/teams/create');
        $form = $client->getCrawler()->filter('form[name=team_edit_form]')->form();

        $editForm = $client->getCrawler()->filter('form[name=team_edit_form]')->form();
        $this->assertEquals('', $editForm->get('team_edit_form[name]')->getValue());
        $this->assertEquals('5', $editForm->get('team_edit_form[teamlead]')->getValue());

        $client->submit($form, [
            'team_edit_form' => [
                'name' => 'Test Team',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/teams/1/edit'));
        $client->followRedirect();
        $this->assertHasFlashSuccess($client);
        $this->assertHasCustomerAndProjectPermissionBoxes($client);
    }

    protected function assertHasCustomerAndProjectPermissionBoxes(Client $client)
    {
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Grant access to customers', $content);
        $this->assertStringContainsString('Grant access to projects', $content);
        $this->assertEquals(1, $client->getCrawler()->filter('form[name=team_customer_form]')->count());
        $this->assertEquals(1, $client->getCrawler()->filter('form[name=team_project_form]')->count());
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new TeamFixtures();
        $fixture->setAmount(2);
        $this->importFixture($em, $fixture);

        $this->assertAccessIsGranted($client, '/admin/teams/1/edit');
        $form = $client->getCrawler()->filter('form[name=team_edit_form]')->form();
        $this->assertNotEmpty($form->get('team_edit_form[name]')->getValue());
        $client->submit($form, [
            'team_edit_form' => [
                'name' => 'Test Team 2'
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/teams/1/edit'));
        $client->followRedirect();
        $editForm = $client->getCrawler()->filter('form[name=team_edit_form]')->form();
        $this->assertEquals('Test Team 2', $editForm->get('team_edit_form[name]')->getValue());
    }
}
