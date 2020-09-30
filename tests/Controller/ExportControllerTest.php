<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;
use Doctrine\ORM\EntityManager;

/**
 * @group integration
 */
class ExportControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/export/');
    }

    public function testIsSecureForrole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/export/');
    }

    public function testIndexActionHasErrorMessageOnEmptyQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/export/?preview=');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasNoEntriesWithFilter($client);
    }

    public function testIndexActionWithEntriesAndTeams()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $user = $this->getUserByRole(User::ROLE_USER);
        /** @var Team $team */
        $team = new Team();
        $team->setName('fooo');
        $team->setTeamLead($teamlead);
        $team->addUser($user);
        $em->persist($team);
        $em->persist($user);
        $em->persist($teamlead);
        $em->flush();

        $user = $this->getUserByRole(User::ROLE_USER);

        $begin = new \DateTime('first day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($user)
            ->setAmount(20)
            ->setStartDate($begin)
            ->setCallback(function (Timesheet $timesheet) use ($team, $em) {
                $team->addProject($timesheet->getProject());
                $em->persist($team);
            })
        ;
        $this->importFixture($fixture);

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($teamlead)
            ->setAmount(2)
            ->setStartDate($begin)
        ;
        $this->importFixture($fixture);
        $em->flush();

        $this->request($client, '/export/?preview=');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // make sure all existing records are displayed
        $this->assertHasDataTable($client);
        // +1 row for summary
        $this->assertDataTableRowCount($client, 'datatable_export', 23);

        // assert export type buttons are available
        $expected = ['csv', 'default.html.twig', 'default-budget.pdf.twig', 'default-internal.pdf.twig', 'default.pdf.twig', 'xlsx'];
        $node = $client->getCrawler()->filter('#export-buttons .startExportBtn');
        $this->assertEquals(\count($expected), $node->count());
        /** @var \DOMElement $button */
        foreach ($node->getIterator() as $button) {
            $type = $button->getAttribute('data-type');
            $this->assertContains($type, $expected);
        }
    }

    public function testIndexActionWithEntriesForTeamleadDoesNotShowUserWithoutTeam()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        $begin = new \DateTime('first day of this month');
        $user = $this->getUserByRole(User::ROLE_USER);

        // these should be ignored, becuase teamlead and user do NOT share a team!
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($user)
            ->setAmount(20)
            ->setStartDate($begin)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/export/?preview=');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // make sure all existing records are displayed
        $this->assertHasNoEntriesWithFilter($client);

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($teamlead)
            ->setAmount(2)
            ->setStartDate($begin)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/export/?preview=');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // make sure all existing records are displayed
        $this->assertHasDataTable($client);
        // +1 row for summary
        $this->assertDataTableRowCount($client, 'datatable_export', 3);

        // assert export type buttons are available
        $expected = ['csv', 'default.html.twig', 'default-budget.pdf.twig', 'default-internal.pdf.twig', 'default.pdf.twig', 'xlsx'];
        $node = $client->getCrawler()->filter('#export-buttons .startExportBtn');
        $this->assertEquals(\count($expected), $node->count());
        /** @var \DOMElement $button */
        foreach ($node->getIterator() as $button) {
            $type = $button->getAttribute('data-type');
            $this->assertContains($type, $expected);
        }
    }

    public function testExportActionWithMissingRenderer()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->request($client, '/export/data', 'POST');

        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Missing export renderer', $response->getContent());
    }

    public function testExportActionWithInvalidRenderer()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/export/', 'GET');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('#export-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/export/data'));
        $node->setAttribute('method', 'POST');

        $client->submit($form, [
            'type' => 'default'
        ]);

        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Unknown export renderer', $response->getContent());
    }

    public function testExportAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $begin = new \DateTime('first day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setAmount(20)
            ->setStartDate($begin)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/export/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('#export-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/export/data'));
        $node->setAttribute('method', 'POST');

        // don't add daterange to make sure the current month is the default range
        $client->submit($form, [
            'type' => 'default.html.twig',
            'markAsExported' => 1
        ]);

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $node = $client->getCrawler()->filter('body');
        $this->assertEquals(1, $node->count());

        // poor mans assertions ;-)
        $this->assertStringContainsString('export_print', $node->getIterator()[0]->getAttribute('class'));
        $this->assertStringContainsString('<h2>', $response->getContent());
        $this->assertStringContainsString('<h3>Summary</h3>', $response->getContent());

        $node = $client->getCrawler()->filter('section.export div#export-records table.dataTable tbody tr');
        // 20 rows + the summary footer
        $this->assertEquals(21, $node->count());

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        /** @var Timesheet $timesheet */
        foreach ($timesheets as $timesheet) {
            $this->assertTrue($timesheet->isExported());
        }
    }
}
