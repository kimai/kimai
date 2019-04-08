<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @coversDefaultClass \App\Controller\ExportController
 * @group integration
 */
class ExportControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/export/');
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/export/');
    }

    public function testIndexActionHasErrorMessageOnEmptyQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/export/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasNoEntriesWithFilter($client);
    }

    public function testIndexActionWithEntries()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $begin = new \DateTime('first day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole($em, User::ROLE_USER))
            ->setAmount(20)
            ->setStartDate($begin)
        ;
        $this->importFixture($em, $fixture);

        $this->request($client, '/export/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // make sure all existing records are displayed
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_export', 20);

        // assert export type buttons are available
        $expected = ['csv', 'html', 'pdf', 'ods', 'xlsx'];
        $node = $client->getCrawler()->filter('#export-buttons button');
        $this->assertEquals(count($expected), $node->count());
        foreach ($node->getIterator() as $button) {
            $type = $button->getAttribute('data-type');
            $this->assertContains($type, $expected);
        }
    }

    public function testExportActionWithMissingRenderer()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->request($client, '/export/data');

        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testExportActionWithInvalidRenderer()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/export/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('#export-form')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/export/data'));
        $client->submit($form, [
            'type' => 'default'
        ]);

        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testExportAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $begin = new \DateTime('first day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole($em, User::ROLE_USER))
            ->setAmount(20)
            ->setStartDate($begin)
        ;
        $this->importFixture($em, $fixture);

        $this->request($client, '/export/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('#export-form')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/export/data'));
        // don't add daterange to make sure the current month is the default range
        $client->submit($form, [
            'type' => 'html'
        ]);

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $node = $client->getCrawler()->filter('body');
        $this->assertEquals(1, $node->count());

        // poor mans assertions ;-)
        $this->assertContains('export_print', $node->getIterator()[0]->getAttribute('class'));
        $this->assertContains('<h2>List of expenses</h2>', $response->getContent());
        $this->assertContains('<h3>Summary</h3>', $response->getContent());

        $node = $client->getCrawler()->filter('section.export div#export-records table.dataTable tbody tr');
        $this->assertEquals(20, $node->count());
    }
}
