<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\ExportTemplate;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\DataFixtures\ExportTemplateFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

#[Group('integration')]
class ExportControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/export/');
    }

    public function testIsSecureForRole(): void
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
            ->setCallback(function (Timesheet $timesheet) use ($team, $em): void {
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

        $header = $client->getCrawler()->filter('section.content div.datatable_export table.dataTable thead th');
        $titles = [];
        /** @var \DOMElement $th */
        foreach ($header as $th) {
            $titles[] = trim($th->textContent);
        }
        self::assertEquals([
            '', 'Date', 'From', 'To', 'User', 'Project', 'Activity', 'Description', 'Tags', 'Duration', 'Unit price', 'Internal price', 'Total price', '',
        ], $titles);

        // assert export type buttons are available
        $expected = [
            'csv' => 'csv',
            'print' => 'html',
            'pdf' => 'pdf',
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
            'print' => 'html',
            'pdf' => 'pdf',
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

        $this->request($client, '/export/', 'GET');
        self::assertTrue($client->getResponse()->isSuccessful());

        $client->request('POST', $this->createUrl('/export/data'), ['_token' => $this->getExportToken($client)]);
        $this->assertRouteNotFound($client);
    }

    public function testExportActionRequiresValidToken(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $client->request('POST', $this->createUrl('/export/data'), ['renderer' => 'print', '_token' => 'not-a-valid-token']);

        $this->assertAccessDenied($client);
    }

    public function testExportActionWithInvalidFormData(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/export/', 'GET');
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->submitExport($client, ['renderer' => 'print', 'daterange' => 'sfsdfsdfsdf']);

        $this->assertAccessDenied($client);
    }

    public function testExportActionRequiresTokenToMarkAsExported(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $begin = new \DateTime('first day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setAmount(5)
            ->setStartDate($begin)
        ;
        $this->importFixture($fixture);

        // simulates a cross-site POST: no token, but all other fields are valid
        $client->request('POST', $this->createUrl('/export/data'), ['renderer' => 'print', 'markAsExported' => 1]);

        $this->assertAccessDenied($client);

        $timesheets = $this->getEntityManager()->getRepository(Timesheet::class)->findAll();
        self::assertCount(5, $timesheets);
        /** @var Timesheet $timesheet */
        foreach ($timesheets as $timesheet) {
            self::assertFalse($timesheet->isExported());
        }
    }

    private function getExportToken(HttpKernelBrowser $client): string
    {
        $token = $client->getCrawler()->filter('div#export-token')->attr('data-value');
        self::assertIsString($token);

        return $token;
    }

    /**
     * Simulates the javascript behind the export buttons: it takes the current state of the
     * toolbar form and posts it, together with the export only fields, to the export route.
     *
     * @param array<string, mixed> $params
     */
    private function submitExport(HttpKernelBrowser $client, array $params): void
    {
        $form = $client->getCrawler()->filter('#export-form')->form();
        $params = array_merge($form->getValues(), $params);
        $params['_token'] = $this->getExportToken($client);

        $client->request('POST', $this->createUrl('/export/data'), $params);
    }

    public function testExportActionWithInvalidRenderer(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/export/', 'GET');
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->submitExport($client, ['renderer' => 'default']);

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

        // don't add daterange to make sure the current month is the default range
        $this->submitExport($client, ['renderer' => 'print', 'markAsExported' => 1]);

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

    public function testCreateTemplateIsSecure(): void
    {
        $this->assertUrlIsSecured('/export/template-create');
    }

    public function testCreateTemplateIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/export/template-create');
    }

    public function testCreateTemplateIsSecureForTeamlead(): void
    {
        // GHSA-rw46-qg69-vg6h
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/export/template-create');
    }

    public function testEditTemplateIsSecureForTeamlead(): void
    {
        // GHSA-rw46-qg69-vg6h
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        /** @var ExportTemplate[] $templates */
        $templates = $this->importFixture(new ExportTemplateFixtures());
        $id = $templates[0]->getId();

        $this->request($client, $this->createUrl('/export/template-edit/' . $id));
        $this->assertAccessDenied($client);
    }

    public function testCreateTemplateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/export/template-create');
        $form = $client->getCrawler()->filter('form[name=export_template_spreadsheet_form]')->form();
        $client->submit($form, [
            'export_template_spreadsheet_form' => [
                'title' => 'My temaplte name',
                'renderer' => 'xlsx',
                'language' => 'de',
                'columns' => 'date',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/export/'));

        $templates = $this->getEntityManager()->getRepository(ExportTemplate::class)->findAll();
        self::assertCount(1, $templates);
        $template = array_pop($templates);
        $id = $template->getId();

        $this->request($client, $this->createUrl('/export/template-edit/' . $id));
        self::assertTrue($client->getResponse()->isSuccessful());
        $editForm = $client->getCrawler()->filter('form[name=export_template_spreadsheet_form]')->form();
        $field = $editForm->get('export_template_spreadsheet_form[title]');
        self::assertInstanceOf(FormField::class, $field);
        self::assertEquals('My temaplte name', $field->getValue());
    }

    public function testEditTemplateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var ExportTemplate[] $templates */
        $templates = $this->importFixture(new ExportTemplateFixtures());

        $id = $templates[0]->getId();

        $this->request($client, $this->createUrl('/export/template-edit/' . $id));
        self::assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form[name=export_template_spreadsheet_form]')->form();
        $field = $form->get('export_template_spreadsheet_form[title]');
        self::assertInstanceOf(FormField::class, $field);
        self::assertEquals('CSV Test', $field->getValue());

        $client->submit($form, [
            'export_template_spreadsheet_form' => [
                'title' => 'My temaplte name',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/export/'));

        /** @var ExportTemplate $template */
        $template = $this->getEntityManager()->getRepository(ExportTemplate::class)->find($id);
        self::assertEquals('My temaplte name', $template->getTitle());
    }
}
