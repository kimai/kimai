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
use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Tests\DataFixtures\InvoiceTemplateFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group integration
 */
class InvoiceControllerTest extends ControllerBaseTest
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

    private function clearInvoiceFiles()
    {
        $path = __DIR__ . '/../_data/invoices/';

        if (is_dir($path)) {
            $files = glob($path . '*');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/invoice/');
    }

    public function testIsSecureForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/invoice/');
    }

    public function testIndexActionRedirectsToCreateTemplate()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->request($client, '/invoice/');
        $this->assertIsRedirect($client, '/invoice/template/create');
    }

    public function testIndexActionHasErrorMessageOnEmptyQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        $id = $templates[0]->getId();

        $this->request($client, '/invoice/?customers[]=1&template=' . $id);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasNoEntriesWithFilter($client);
    }

    public function testListTemplateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $this->importFixture($fixture);

        $this->request($client, '/invoice/template');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
    }

    public function testCreateTemplateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/invoice/template/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=invoice_template_form]')->form();
        $client->submit($form, [
            'invoice_template_form' => [
                'name' => 'Test',
                'title' => 'Test invoice template',
                'company' => 'Company name',
                'renderer' => 'default',
                'calculator' => 'default',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/invoice/template'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);
    }

    public function testCopyTemplateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        /** @var InvoiceTemplate $template */
        $template = $templates[0];

        $this->request($client, '/invoice/template/create/' . $template->getId());
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=invoice_template_form]')->form();
        $values = $form->getPhpValues()['invoice_template_form'];
        $this->assertEquals('Copy of ' . $template->getName(), $values['name']);
        $this->assertEquals($template->getTitle(), $values['title']);
        $this->assertEquals($template->getDueDays(), $values['dueDays']);
        $this->assertEquals($template->getCalculator(), $values['calculator']);
        $this->assertEquals($template->getVat(), $values['vat']);
        $this->assertEquals($template->getRenderer(), $values['renderer']);
        $this->assertEquals($template->getCompany(), $values['company']);
        $this->assertEquals($template->getAddress(), $values['address']);
        $this->assertEquals($template->getPaymentTerms(), $values['paymentTerms']);
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
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
            $this->assertFalse($timesheet->isExported());
        }

        $this->request($client, '/invoice/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $begin->format('Y-m-d') . DateRangeType::DATE_SPACER . $end->format('Y-m-d');

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/invoice/'));
        $node->setAttribute('method', 'GET');
        $client->submit($form, [
            'template' => $template->getId(),
            'daterange' => $dateRange,
            'customers' => [1],
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());

        // no warning should be displayed
        $node = $client->getCrawler()->filter('div.callout.callout-warning.lead');
        $this->assertEquals(0, $node->count());
        // but the datatable with all timesheets
        $this->assertDataTableRowCount($client, 'datatable_invoice', 20);

        $urlParams = [
            'daterange' => $dateRange,
            'projects[]' => 1,
            'markAsExported' => 1,
        ];

        $action = '/invoice/save-invoice/1/' . $template->getId() . '?' . http_build_query($urlParams);
        $this->request($client, $action);
        $this->assertIsRedirect($client);
        $this->assertRedirectUrl($client, '/invoice/show?id=', false);
        $client->followRedirect();
        $this->assertDataTableRowCount($client, 'datatable_invoices', 1);

        $em = $this->getEntityManager();
        $em->clear();
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertCount(20, $timesheets);
        /** @var Timesheet $timesheet */
        foreach ($timesheets as $timesheet) {
            $this->assertTrue($timesheet->isExported());
        }
    }

    public function testPrintAction()
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
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $begin->format('Y-m-d') . DateRangeType::DATE_SPACER . $end->format('Y-m-d');

        $params = [
            'daterange' => $dateRange,
            'projects' => [1],
        ];

        $action = '/invoice/preview/1/' . $id . '?' . http_build_query($params);
        $this->request($client, $action);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $node = $client->getCrawler()->filter('body');
        $this->assertEquals(1, $node->count());
        $this->assertEquals('invoice_print', $node->getIterator()[0]->getAttribute('class'));
    }

    public function testCreateActionAsAdminWithDownloadAndStatusChangeAndDelete()
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
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $begin->format('Y-m-d') . DateRangeType::DATE_SPACER . $end->format('Y-m-d');

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/invoice/?preview='));
        $node->setAttribute('method', 'GET');
        $client->submit($form, [
            'template' => $template->getId(),
            'daterange' => $dateRange,
            'customers' => [1],
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());

        // no warning should be displayed
        $node = $client->getCrawler()->filter('div.callout.callout-warning.lead');
        $this->assertEquals(0, $node->count());
        // but the datatable with all timesheets
        $this->assertDataTableRowCount($client, 'datatable_invoice', 20);

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/invoice/?createInvoice=true'));
        $node->setAttribute('method', 'GET');
        $client->submit($form, [
            'template' => $template->getId(),
            'daterange' => $dateRange,
            'customers' => [1],
            'projects' => [1],
            'markAsExported' => 1,
        ]);

        $invoices = $this->getEntityManager()->getRepository(Invoice::class)->findAll();
        $id = $invoices[0]->getId();

        $this->assertIsRedirect($client, '/invoice/show?id=' . $id);
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_invoices', 1);

        // make sure the invoice is saved
        $this->request($client, '/invoice/download/' . $id);
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertFileExists($response->getFile());

        $this->request($client, '/invoice/change-status/' . $id . '/pending');
        $this->assertIsRedirect($client, '/invoice/show');
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->request($client, '/invoice/change-status/' . $id . '/paid');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasValidationError(
            $client,
            '/invoice/change-status/' . $id . '/paid',
            'form[name=invoice_payment_date_form]',
            [
                'invoice_payment_date_form' => [
                    'paymentDate' => 'invalid'
                ]
            ],
            ['#invoice_payment_date_form_paymentDate']
        );

        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=invoice_payment_date_form]')->form();
        $client->submit($form, [
            'invoice_payment_date_form' => [
                'paymentDate' => (new \DateTime())->format('Y-m-d')
            ]
        ]);

        $this->assertIsRedirect($client, '/invoice/show');
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->request($client, '/invoice/change-status/' . $id . '/new');
        $this->assertIsRedirect($client, '/invoice/show');
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        // this does not delete the invoice, because the token is wrong
        $this->request($client, '/invoice/delete/' . $id . '/fghfkjhgkjhg');
        $this->assertIsRedirect($client, '/invoice/show');
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testEditTemplateAction()
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
                'company' => 'Company name',
                'renderer' => 'default',
                'calculator' => 'default',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/invoice/template'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);
    }

    public function testDeleteTemplateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $template = $this->importFixture($fixture);
        $id = $template[0]->getId();

        $token = self::$container->get('security.csrf.token_manager')->getToken('invoice.delete_template');

        $this->request($client, '/invoice/template/' . $id . '/delete/' . $token);
        $this->assertIsRedirect($client, '/invoice/template');
        $client->followRedirect();

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $this->assertEquals(0, $this->getEntityManager()->getRepository(InvoiceTemplate::class)->count([]));
    }

    public function testUploadDocumentAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $this->importFixture($fixture);

        $this->request($client, '/invoice/document_upload');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('form[name=invoice_document_upload_form]');
        self::assertEquals(1, $node->count(), 'Could not find upload form');
        // we do not test the upload here, just make sure that the action can be rendered properly
    }
}
