<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\ExportableItem;
use App\Entity\ExportTemplate;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Event\TimesheetMetaDisplayEvent;
use App\Export\ExportPreviewColumnProviderInterface;
use App\Export\ExportRepositoryInterface;
use App\Export\ServiceExport;
use App\Repository\Query\ExportQuery;
use App\Tests\DataFixtures\ExportTemplateFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

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
                $project = $timesheet->getProject();
                if ($project !== null) {
                    $team->addProject($project);
                }
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

    public function testIndexActionShowsMetaFieldColumns(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $begin = new \DateTime('first day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setAmount(2)
            ->setStartDate($begin)
            ->setCallback(function (Timesheet $timesheet): void {
                $timesheet->setMetaField(
                    (new TimesheetMeta())
                        ->setName('foo')
                        ->setValue('Timesheet value')
                        ->setIsVisible(true)
                );
            })
        ;
        $this->importFixture($fixture);

        $this->request($client, '/export/?performSearch=performSearch');
        self::assertTrue($client->getResponse()->isSuccessful());

        $modal = $client->getCrawler()->filter('#modal_export');
        self::assertEquals(1, $modal->count());

        $html = $modal->html();
        self::assertStringContainsString('column_mf_74696d657368656574_666f6f', $html);
        self::assertStringContainsString('column_mf_74696d657368656574_666f6f32', $html);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('Timesheet value', $content);

        $columns = $client->getCrawler()->filter('section.content div.datatable_export table.dataTable thead th');
        $found = false;
        /** @var \DOMElement $th */
        foreach ($columns as $th) {
            if ($th->getAttribute('data-field') === 'mf_74696d657368656574_666f6f') {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, 'Typed meta field column not found in datatable header');
    }

    public function testIndexActionDoesNotRequestPreviewColumnsWithoutSearch(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $this->request($client, '/export/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $modal = $client->getCrawler()->filter('#modal_export');
        self::assertEquals(1, $modal->count());
        self::assertStringNotContainsString('column_mf_', $modal->html());
    }

    public function testIndexActionDoesNotTranslateResolvedColumnLabelsTwice(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        self::assertInstanceOf(KernelBrowser::class, $client);
        $client->disableReboot();

        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'Working place' => 'Resolved label',
            'Resolved label' => 'Translated twice',
        ], 'en');
        self::getContainer()->set('translator', new DataCollectorTranslator($translator));

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addListener(TimesheetMetaDisplayEvent::class, static function (TimesheetMetaDisplayEvent $event): void {
            $event->addField(
                (new TimesheetMeta())
                    ->setName('foo')
                    ->setLabel('Working place')
                    ->setType(TextType::class)
                    ->setIsVisible(true)
            );
        });

        $this->request($client, '/export/?performSearch=performSearch');
        self::assertTrue($client->getResponse()->isSuccessful());

        $modal = $client->getCrawler()->filter('#modal_export')->text();
        self::assertStringContainsString('Resolved label', $modal);
        self::assertStringNotContainsString('Translated twice', $modal);
    }

    public function testIndexActionKeepsMixedRepositoryMetaColumnsTypedAndAligned(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setAmount(1)
            ->setStartDate(new \DateTime('first day of this month'))
            ->setCallback(function (Timesheet $timesheet): void {
                $timesheet->setMetaField(
                    (new TimesheetMeta())
                        ->setName('foo')
                        ->setValue('Timesheet value')
                        ->setIsVisible(true)
                );
            })
        ;
        $timesheets = $this->importFixture($fixture);
        $source = $timesheets[0];
        $begin = $source->getBegin();
        $end = $source->getEnd();
        self::assertNotNull($begin);
        self::assertNotNull($end);

        $field = $this->createMock(MetaTableTypeInterface::class);
        $field->method('getName')->willReturn('foo');
        $field->method('getLabel')->willReturn('Working place');
        $field->method('getType')->willReturn(TextType::class);

        $expenseMeta = $this->createMock(MetaTableTypeInterface::class);
        $expenseMeta->method('merge')->with($field)->willReturnSelf();
        $expenseMeta->method('getType')->willReturn(TextType::class);
        $expenseMeta->method('getValue')->willReturn('Expense value');

        $expense = $this->createMock(ExportableItem::class);
        $expense->method('getType')->willReturn('expense');
        $expense->method('getUser')->willReturn($source->getUser());
        $expense->method('getProject')->willReturn($source->getProject());
        $expense->method('getActivity')->willReturn($source->getActivity());
        $expense->method('getBegin')->willReturn(clone $begin);
        $expense->method('getEnd')->willReturn(clone $end);
        $expense->method('getDuration')->willReturn($source->getDuration());
        $expense->method('getRate')->willReturn($source->getRate());
        $expense->method('getInternalRate')->willReturn($source->getInternalRate());
        $expense->method('getHourlyRate')->willReturn($source->getHourlyRate());
        $expense->method('getFixedRate')->willReturn($source->getFixedRate());
        $expense->method('getMetaField')->with('foo')->willReturn($expenseMeta);

        $repository = new class($expense, $field) implements ExportRepositoryInterface, ExportPreviewColumnProviderInterface {
            public function __construct(
                private readonly ExportableItem $item,
                private readonly MetaTableTypeInterface $field,
            ) {
            }

            public function setExported(array $items): void
            {
            }

            public function getExportItemsForQuery(ExportQuery $query): iterable
            {
                return [$this->item];
            }

            public function getType(): string
            {
                return 'expense';
            }

            public function getExportPreviewColumns(ExportQuery $query): array
            {
                return [$this->field];
            }
        };

        /** @var ServiceExport $service */
        $service = self::getContainer()->get(ServiceExport::class);
        $service->addExportRepository($repository);

        $this->request($client, '/export/?performSearch=performSearch');
        self::assertTrue($client->getResponse()->isSuccessful());

        $headers = $client->getCrawler()->filter('section.content div.datatable_export table.dataTable thead th');
        $fields = [];
        $titles = [];
        /** @var \DOMElement $header */
        foreach ($headers as $header) {
            $fields[] = $header->getAttribute('data-field');
            $titles[] = trim($header->textContent);
        }

        self::assertContains('mf_74696d657368656574_666f6f', $fields);
        self::assertContains('mf_657870656e7365_666f6f', $fields);
        self::assertContains('timesheet: Working place', $titles);
        self::assertContains('expense: Working place', $titles);

        $rows = $client->getCrawler()->filter('section.content div.datatable_export table.dataTable tbody tr');
        self::assertCount(2, $rows);

        $rowTexts = [];
        /** @var \DOMElement $row */
        foreach ($rows as $row) {
            self::assertCount($headers->count(), $row->getElementsByTagName('td'));
            $rowTexts[] = trim($row->textContent);
        }

        self::assertCount(1, array_filter($rowTexts, static fn (string $text): bool => str_contains($text, 'Timesheet value') && !str_contains($text, 'Expense value')));
        self::assertCount(1, array_filter($rowTexts, static fn (string $text): bool => str_contains($text, 'Expense value') && !str_contains($text, 'Timesheet value')));
    }

    public function testIndexActionSkippedRowsWarningSpansAllColumns(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        // the warning row is only rendered above the 500 entries preview limit
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setAmount(501)
            ->setFixedStartDate(new \DateTime('first day of this month'))
        ;
        $this->importFixture($fixture);

        $this->request($client, '/export/?performSearch=performSearch');
        self::assertTrue($client->getResponse()->isSuccessful());

        $warning = $client->getCrawler()->filter('section.content div.datatable_export table.dataTable tr.warning td');
        self::assertEquals(1, $warning->count());

        $fields = [];
        /** @var \DOMElement $th */
        foreach ($client->getCrawler()->filter('section.content div.datatable_export table.dataTable thead th') as $th) {
            $fields[] = $th->getAttribute('data-field');
        }

        // plugin meta columns are part of the header, so the span is only correct when it is counted
        self::assertContains('mf_74696d657368656574_666f6f', $fields);
        self::assertEquals(\count($fields), (int) $warning->attr('colspan'));
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
        self::assertIsString($content);
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
        self::assertNotNull($template);
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
