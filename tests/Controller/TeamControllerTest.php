<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Tests\DataFixtures\TeamFixtures;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class TeamControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/admin/teams/');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/admin/teams/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $em = $this->getEntityManager();
        $fixture = new TeamFixtures();
        $fixture->setAmount(5);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/teams/');
        $this->assertPageActions($client, [
            'create modal-ajax-form' => $this->createUrl('/admin/teams/create'),
        ]);
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_admin_teams', 6);
    }

    public function testIndexActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $fixture = new TeamFixtures();
        $fixture->setAmount(5);
        $fixture->setCallback(function (Team $team) {
            $team->setName($team->getName() . '- fantastic team with foooo bar magic');
        });
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/teams/');

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'searchTerm' => 'foo',
        ]);

        self::assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_admin_teams', 5);
    }

    public function testCreateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/teams/create');
        $form = $client->getCrawler()->filter('form[name=team_edit_form]')->form();

        self::assertEquals('', $form->get('team_edit_form[name]')->getValue());

        $values = $form->getPhpValues();
        $values['team_edit_form']['name'] = 'Test Team' . uniqid();
        $values['team_edit_form']['members'][0]['user'] = 5;
        $values['team_edit_form']['members'][0]['teamlead'] = 1;
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $location = $this->assertIsModalRedirect($client, '/edit');
        $this->requestPure($client, $location);

        $this->assertHasFlashSuccess($client);
        $this->assertHasCustomerAndProjectPermissionBoxes($client);
    }

    protected function assertHasCustomerAndProjectPermissionBoxes(HttpKernelBrowser $client): void
    {
        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('Grant access to customers', $content);
        self::assertStringContainsString('Grant access to projects', $content);
        self::assertEquals(1, $client->getCrawler()->filter('form[name=team_customer_form]')->count());
        self::assertEquals(1, $client->getCrawler()->filter('form[name=team_project_form]')->count());
    }

    public function testEditAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new TeamFixtures();
        $fixture->setAmount(2);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/teams/1/edit');
        $form = $client->getCrawler()->filter('form[name=team_edit_form]')->form();

        $client->submit($form, [
            'team_edit_form' => [
                'name' => 'Test Team 2'
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/teams/1/edit'));
        $client->followRedirect();
        $editForm = $client->getCrawler()->filter('form[name=team_edit_form]')->form();
        self::assertEquals('Test Team 2', $editForm->get('team_edit_form[name]')->getValue());
    }

    public function testEditMemberAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new TeamFixtures();
        $fixture->setAmount(2);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/teams/1/edit_member');
        $form = $client->getCrawler()->filter('form[name=team_edit_form]')->form();
        $client->submit($form, [
            'team_edit_form' => [
                'name' => 'Test Team 2'
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/teams/1/edit'));
        $client->followRedirect();
        $editForm = $client->getCrawler()->filter('form[name=team_edit_form]')->form();
        self::assertEquals('Test Team 2', $editForm->get('team_edit_form[name]')->getValue());
    }

    public function testDuplicateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->request($client, '/admin/teams/1/duplicate');
        $form = $client->getCrawler()->filter('form[name=team_edit_form]')->form();

        $client->submit($form);

        $location = $this->assertIsModalRedirect($client);
        $this->requestPure($client, $location);

        $editForm = $client->getCrawler()->filter('form[name=team_edit_form]')->form();
        self::assertEquals('Test team (1)', $editForm->get('team_edit_form[name]')->getValue());
    }
}
