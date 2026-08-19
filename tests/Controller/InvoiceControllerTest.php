<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceTemplate;
use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\DataFixtures\InvoiceFixtures;
use App\Tests\DataFixtures\InvoiceTemplateFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\User\PermissionService;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class InvoiceControllerTest extends AbstractControllerBaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearInvoiceFiles();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearInvoiceFiles();
    }

    private function clearInvoiceFiles(): void
    {
        $path = __DIR__ . '/../_data/invoices/';

        if (is_dir($path)) {
            $files = glob($path . '*');
            if ($files === false) {
                return;
            }
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/invoice/');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/invoice/');
    }

    public function testIndexActionRedirectsToCreateTemplate(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->request($client, '/invoice/');
        $this->assertIsRedirect($client, '/invoice/template/create');
    }

    public function testIndexActionHasErrorMessageOnEmptyQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        $id = $templates[0]->getId();

        $this->request($client, '/invoice/?customers[]=1&template=' . $id);
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasNoEntriesWithFilter($client);
    }

    public function testListTemplateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $this->importFixture($fixture);

        $this->request($client, '/invoice/template');

        self::assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
    }

    public function testCreateTemplateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/invoice/template/create');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=invoice_template_form]')->form();
        $client->submit($form, [
            'invoice_template_form' => [
                'name' => 'FooBar Template',
                'title' => 'Test invoice template',
                'customer' => 1,
                'renderer' => 'default',
                'calculator' => 'default',
                'vat' => '27,937',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/invoice/template'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $template = $this->getEntityManager()->getRepository(InvoiceTemplate::class)->findAll()[0];
        self::assertEquals('FooBar Template', $template->getName());
        self::assertEquals('Test invoice template', $template->getTitle());
        self::assertEquals('Test', $template->getCompany());
        self::assertEquals('default', $template->getRenderer());
        self::assertEquals('default', $template->getCalculator());
        self::assertEquals('27.937', $template->getVat());
    }

    public function testCopyTemplateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        /** @var InvoiceTemplate $template */
        $template = $templates[0];

        $this->request($client, '/invoice/template/create/' . $template->getId());
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=invoice_template_form]')->form();
        $values = $form->getPhpValues()['invoice_template_form'];
        self::assertEquals($template->getName() . ' (1)', $values['name']);
        self::assertEquals($template->getTitle(), $values['title']);
        self::assertEquals($template->getDueDays(), $values['dueDays']);
        self::assertEquals($template->getCalculator(), $values['calculator']);
        self::assertEquals($template->getVat(), $values['vat']);
        self::assertEquals($template->getRenderer(), $values['renderer']);
        self::assertEquals($template->getPaymentTerms(), $values['paymentTerms']);
    }

    public function testCreateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        /** @var InvoiceTemplate $template */
        $template = $templates[0];

        $begin = new \DateTime('first day of this month');
        $end = new \DateTime('last day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole(User::ROLE_TEAMLEAD))
            ->setAmount(20)
            ->setStartDate($begin)
        ;
        $timesheets = $this->importFixture($fixture);
        foreach ($timesheets as $timesheet) {
            self::assertFalse($timesheet->isExported());
        }

        $this->request($client, '/invoice/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $this->formatDateRange($begin, $end);

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/invoice/'));
        $node->setAttribute('method', 'GET');
        $client->submit($form, [
            'template' => $template->getId(),
            'daterange' => $dateRange,
            'customers' => [1],
        ]);

        self::assertTrue($client->getResponse()->isSuccessful());

        // no warning should be displayed
        $node = $client->getCrawler()->filter('div.callout.callout-warning.lead');
        self::assertEquals(0, $node->count());
        // but the datatable with all timesheets
        $this->assertDataTableRowCount($client, 'datatable_invoice_create', 20);

        $urlParams = [
            'daterange' => $dateRange,
            'projects[]' => 1,
            'template' => $template->getId(),
        ];

        $token = $client->getCrawler()->filter('div#create-token')->attr('data-value');
        self::assertIsString($token);

        $urlParams['_token'] = $token;

        $client->request('POST', $this->createUrl('/invoice/save-invoice/1'), $urlParams);
        $this->assertIsRedirect($client, '/invoice/show?id=', false);
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
        $this->assertDataTableRowCount($client, 'datatable_invoices', 1);

        $em = $this->getEntityManager();
        $em->clear();
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertCount(20, $timesheets);
        /** @var Timesheet $timesheet */
        foreach ($timesheets as $timesheet) {
            self::assertTrue($timesheet->isExported());
        }
    }

    public function testPreviewAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        $id = $templates[0]->getId();

        $begin = new \DateTime('first day of this month');
        $end = new \DateTime('last day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole(User::ROLE_TEAMLEAD))
            ->setAmount(20)
            ->setStartDate($begin)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/invoice/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $this->formatDateRange($begin, $end);

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/invoice/'));
        $node->setAttribute('method', 'GET');
        $client->submit($form, [
            'template' => $id,
            'daterange' => $dateRange,
            'customers' => [1],
        ]);

        self::assertTrue($client->getResponse()->isSuccessful());

        $params = [
            'daterange' => $dateRange,
            'projects' => [1],
            'template' => $id,
            'customers[]' => 1
        ];

        $token = $client->getCrawler()->filter('div#preview-token')->attr('data-value');
        $action = '/invoice/preview/1/' . $token . '?' . http_build_query($params);

        $this->request($client, $action);
        self::assertTrue($client->getResponse()->isSuccessful());
        $node = $client->getCrawler()->filter('body');
        self::assertEquals(1, $node->count());

        /** @var \DOMElement $element */
        $element = $node->getIterator()[0];
        self::assertEquals('invoice_print', $element->getAttribute('class'));
    }

    public function testCreateActionAsAdminWithDownloadAndStatusChange(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        $template = $templates[0];

        $begin = new \DateTime('first day of this month');
        $end = new \DateTime('last day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole(User::ROLE_ADMIN))
            ->setAmount(20)
            ->setStartDate($begin)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/invoice/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $this->formatDateRange($begin, $end);

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/invoice/'));
        $node->setAttribute('method', 'GET');
        $client->submit($form, [
            'template' => $template->getId(),
            'daterange' => $dateRange,
            'customers' => [1],
        ]);

        self::assertTrue($client->getResponse()->isSuccessful());

        // no warning should be displayed
        $node = $client->getCrawler()->filter('div.callout.callout-warning.lead');
        self::assertEquals(0, $node->count());
        // but the datatable with all timesheets
        $this->assertDataTableRowCount($client, 'datatable_invoice_create', 20);

        $token = $client->getCrawler()->filter('div#create-token')->attr('data-value');
        self::assertIsString($token);

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/invoice/save-invoice/1'));
        $node->setAttribute('method', 'POST');
        $client->submit($form, [
            'template' => $template->getId(),
            'daterange' => $dateRange,
            'projects' => [1],
            '_token' => $token,
        ]);

        $this->assertIsRedirect($client, '/invoice/show?id=', false);
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $invoices = $this->getEntityManager()->getRepository(Invoice::class)->findAll();
        self::assertCount(1, $invoices);
        $id = $invoices[0]->getId();

        $this->assertHasFlashSuccess($client);

        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_invoices', 1);

        // make sure the invoice is saved
        $this->request($client, '/invoice/download/' . $id);
        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertFileExists($response->getFile());

        $this->request($client, '/invoice/show');
        self::assertTrue($client->getResponse()->isSuccessful());
        $link = $client->getCrawler()->selectLink('Waiting for payment');
        $statusUrl = $link->attr('data-href');
        self::assertIsString($statusUrl);
        // the status change is a POST and the token is never part of the URL
        self::assertEquals('#', $link->attr('href'));

        $statusToken = $client->getCrawler()->filter('div#status-token')->attr('data-value');
        self::assertIsString($statusToken);
        $client->request('POST', $statusUrl, ['_token' => $statusToken]);
        $this->assertIsRedirect($client, '/invoice/show');
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $link = $client->getCrawler()->selectLink('Invoice paid');
        $url = $link->attr('href');
        $this->request($client, $url);
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasValidationError(
            $client,
            $url,
            'form[name=invoice_edit_form]',
            [
                'invoice_edit_form' => [
                    'paymentDate' => 'invalid'
                ]
            ],
            ['#invoice_edit_form_paymentDate']
        );

        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=invoice_edit_form]')->form();
        $client->submit($form, [
            'invoice_edit_form' => [
                'paymentDate' => (new \DateTime())->format(self::DEFAULT_DATE_FORMAT)
            ]
        ]);

        $this->assertIsRedirect($client, '/invoice/show');
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $statusToken = $client->getCrawler()->filter('div#status-token')->attr('data-value');
        self::assertIsString($statusToken);
        $client->request('POST', $this->createUrl('/invoice/change-status/' . $id . '/new'), ['_token' => $statusToken]);
        $this->assertIsRedirect($client, '/invoice/show');
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * Regression tests for GHSA-4xmp-xqv9-pcrf.
     *
     * Creating an invoice is a state-changing operation: it must not be reachable via
     * GET and the CSRF token must be sent in the request body, never in the URL.
     */
    public function testCreateInvoiceIsNotReachableWithGet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/invoice/save-invoice/1');
        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testCreateInvoiceRequiresValidToken(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $client->request('POST', $this->createUrl('/invoice/save-invoice/1'), ['_token' => 'not-a-valid-token']);

        $this->assertIsRedirect($client, '/invoice/');
        $client->followRedirect();
        $this->assertHasFlashError($client);

        self::assertCount(0, $this->getEntityManager()->getRepository(Invoice::class)->findAll());
    }

    public function testChangeStatusIsNotReachableWithGet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $fixture = new InvoiceFixtures();
        $fixture->setAmount(1);
        $invoice = $this->importFixture($fixture)[0];

        $this->request($client, '/invoice/change-status/' . $invoice->getId() . '/canceled');
        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testChangeStatusRequiresValidToken(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $fixture = new InvoiceFixtures();
        $fixture->setAmount(1);
        $fixture->setStatus([Invoice::STATUS_NEW]);
        $invoice = $this->importFixture($fixture)[0];
        $invoiceId = $invoice->getId();
        self::assertIsInt($invoiceId);

        $client->request('POST', $this->createUrl('/invoice/change-status/' . $invoiceId . '/canceled'), ['_token' => 'not-a-valid-token']);

        $this->assertIsRedirect($client, '/invoice/show');
        $client->followRedirect();
        $this->assertHasFlashError($client);

        $em = $this->getEntityManager();
        $em->clear();
        $reloaded = $em->getRepository(Invoice::class)->find($invoiceId);
        self::assertInstanceOf(Invoice::class, $reloaded);
        self::assertEquals(Invoice::STATUS_NEW, $reloaded->getStatus());
    }

    /**
     * The invoice listing must not render the status token into any URL, as URLs end up
     * in access logs, proxy logs, the browser history and referrer headers.
     */
    public function testInvoiceActionUrlsDoNotContainTheStatusToken(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $em = $this->getEntityManager();
        $role = (new Role())->setName(User::ROLE_SUPER_ADMIN);
        $em->persist($role);
        $em->flush();

        $permissionService = self::getContainer()->get(PermissionService::class);
        self::assertInstanceOf(PermissionService::class, $permissionService);
        $permissionService->saveRolePermission(
            (new RolePermission())->setRole($role)->setPermission('delete_invoice')->setAllowed(true)
        );

        $fixture = new InvoiceFixtures();
        $fixture->setAmount(1);
        $fixture->setStatus([Invoice::STATUS_NEW]);
        $invoice = $this->importFixture($fixture)[0];

        $this->request($client, '/invoice/show');
        self::assertTrue($client->getResponse()->isSuccessful());

        $token = $client->getCrawler()->filter('div#status-token')->attr('data-value');
        self::assertIsString($token);
        self::assertNotEmpty($token);

        $urls = $client->getCrawler()->filter('a')->each(fn ($node) => $node->attr('href'));
        foreach ($urls as $url) {
            if ($url === null) {
                continue;
            }
            self::assertStringNotContainsString($token, $url);
            self::assertStringNotContainsString('/change-status/', $url);
        }

        // deleting goes through the API, which does not need a CSRF token at all
        $delete = $client->getCrawler()->filter('a.api-link[data-method=DELETE]');
        self::assertEquals(1, $delete->count());
        self::assertEquals('/api/invoices/' . $invoice->getId(), $delete->attr('href'));
    }

    /**
     * The "mark as paid" action only renders the edit form, so it may stay a GET route.
     */
    public function testMarkPaidActionRendersForm(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $fixture = new InvoiceFixtures();
        $fixture->setAmount(1);
        $fixture->setStatus([Invoice::STATUS_PENDING]);
        $invoice = $this->importFixture($fixture)[0];

        $this->request($client, '/invoice/mark-paid/' . $invoice->getId());
        self::assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('form[name=invoice_edit_form]');
        self::assertEquals(1, $node->count());
    }

    public function testEditTemplateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $template = $this->importFixture($fixture);
        $id = $template[0]->getId();

        $this->request($client, '/invoice/template/' . $id . '/edit?page=1');
        $form = $client->getCrawler()->filter('form[name=invoice_template_form]')->form();
        $client->submit($form, [
            'invoice_template_form' => [
                'name' => 'Test 2!',
                'title' => 'Test invoice template',
                'customer' => 1,
                'renderer' => 'default',
                'calculator' => 'default',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/invoice/template'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);
    }

    public function testDeleteTemplateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $template = $this->importFixture($fixture);
        $id = $template[0]->getId();

        $this->request($client, '/invoice/template');
        $url = $this->createUrl('/invoice/template/' . $id . '/delete/');
        $links = $client->getCrawler()->filterXPath("//a[starts-with(@href, '" . $url . "')]");

        $this->requestPure($client, $links->attr('href'));
        $this->assertIsRedirect($client, '/invoice/template');
        $client->followRedirect();

        self::assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        self::assertEquals(0, $this->getEntityManager()->getRepository(InvoiceTemplate::class)->count([]));
    }

    public function testUploadDocumentAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $this->importFixture($fixture);

        $this->request($client, '/invoice/document_upload');
        self::assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('form[name=invoice_document_upload_form]');
        self::assertEquals(1, $node->count(), 'Could not find upload form');
        // we do not test the upload here, just make sure that the action can be rendered properly
    }

    public function testExportIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/invoice/export');
    }

    public function testExportAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/invoice/export');
        $this->assertExcelExportResponse($client, 'kimai-invoices_');
    }
}
