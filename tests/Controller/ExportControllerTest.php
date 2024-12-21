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
class ExportControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/export/');
    }

    public function testIsSecureForrole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/export/');
    }

    public function testIndexActionHasErrorMessageOnEmptyQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/export/?performSearch=performSearch');
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasNoEntriesWithFilter($client);
    }

    public function testIndexActionWithEntriesAndTeams(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $user = $this->getUserByRole(User::ROLE_USER);
        /** @var Team $team */
        $team = new Team('fooo');
        $team->addTeamlead($teamlead);
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

        $this->request($client, '/export/?performSearch=performSearch');
        self::assertTrue($client->getResponse()->isSuccessful());

        // make sure all existing records are displayed
        $this->assertHasDataTable($client);
        // +1 row for summary
        $this->assertDataTableRowCount($client, 'datatable_export', 22);

        // assert export type buttons are available
        $expected = [
            'csv' => 'csv',
            'default.html.twig' => 'default.html.twig',
            'default-budget.pdf.twig' => 'default-budget.pdf.twig',
            'default-internal.pdf.twig' => 'default-internal.pdf.twig',
            'default.pdf.twig' => 'default.pdf.twig',
            'xlsx' => 'xlsx'
        ];
        $node = $client->getCrawler()->filter('#export-buttons .startExportBtn');
        $this->assertGreaterThanOrEqual(\count($expected), $node->count());
        /** @var \DOMElement $button */
        foreach ($node->getIterator() as $button) {
            $type = $button->getAttribute('data-type');
            unset($expected[$type]);
        }
        self::assertEmpty($expected);
    }

    public function testIndexActionWithEntriesForTeamleadDoesNotShowUserWithoutTeam(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

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

        $this->request($client, '/export/?performSearch=performSearch');
        self::assertTrue($client->getResponse()->isSuccessful());

        // make sure existing records are not displayed
        $this->assertHasNoEntriesWithFilter($client);

        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($teamlead)
            ->setAmount(2)
            ->setStartDate($begin)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/export/?performSearch=performSearch');
        self::assertTrue($client->getResponse()->isSuccessful());

        // make sure all existing records are displayed
        $this->assertHasDataTable($client);
        // +1 row for summary
        $this->assertDataTableRowCount($client, 'datatable_export', 2);

        // assert export type buttons are available
        $expected = [
            'csv' => 'csv',
            'default.html.twig' => 'default.html.twig',
            'default-budget.pdf.twig' => 'default-budget.pdf.twig',
            'default-internal.pdf.twig' => 'default-internal.pdf.twig',
            'default.pdf.twig' => 'default.pdf.twig',
            'xlsx' => 'xlsx'
        ];
        $node = $client->getCrawler()->filter('#export-buttons .startExportBtn');
        $this->assertGreaterThanOrEqual(\count($expected), $node->count());
        /** @var \DOMElement $button */
        foreach ($node->getIterator() as $button) {
            $type = $button->getAttribute('data-type');
            unset($expected[$type]);
        }
        self::assertEmpty($expected);
    }

    public function testExportActionWithMissingRenderer(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->request($client, '/export/data', 'POST');
        $this->assertRouteNotFound($client);
    }

    public function testExportActionWithInvalidRenderer(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/export/', 'GET');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('#export-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/export/data'));
        $node->setAttribute('method', 'POST');

        $client->submit($form, [
            'renderer' => 'default'
        ]);

        $this->assertRouteNotFound($client);
    }

    public function testExportAction(): void
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('#export-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/export/data'));
        $node->setAttribute('method', 'POST');

        // don't add daterange to make sure the current month is the default range
        $client->submit($form, [
            'renderer' => 'default.html.twig',
            'markAsExported' => 1
        ]);

        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());
        $content = $response->getContent();
        $node = $client->getCrawler()->filter('body');
        self::assertEquals(1, $node->count());

        // poor mans assertions ;-)
        /** @var \DOMElement $element */
        $element = $node->getIterator()[0];
        self::assertStringContainsString('export_print', $element->getAttribute('class'));
        self::assertStringContainsString('<h2 id="doc-title" contenteditable="true"', $content);
        self::assertStringContainsString('<h3 class="card-title" id="doc-summary" contenteditable="true" data-original="Summary">Summary</h3>', $content);

        $node = $client->getCrawler()->filter('section.export div#export-records table.dataTable tbody tr');
        // 20 rows + the summary footer
        self::assertEquals(21, $node->count());

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        /** @var Timesheet $timesheet */
        foreach ($timesheets as $timesheet) {
            self::assertTrue($timesheet->isExported());
        }
    }
}
