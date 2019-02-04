<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\InvoiceTemplate;
use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Tests\DataFixtures\InvoiceFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @coversDefaultClass \App\Controller\InvoiceController
 * @group integration
 */
class InvoiceControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/invoice/');
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/invoice/');
    }

    public function testIndexActionRedirectsToCreateTemplate()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/invoice/');
        $this->assertIsRedirect($client, '/invoice/template/create');
    }

    public function testIndexActionHasErrorMessageOnEmptyQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new InvoiceFixtures();
        $this->importFixture($em, $fixture);

        $this->request($client, '/invoice/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('div.callout.callout-warning.lead');
        $this->assertNotEmpty($node->text());
        $this->assertContains('No entries were found based on your selected filters.', $node->text());
    }

    public function testListTemplateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new InvoiceFixtures();
        $this->importFixture($em, $fixture);

        $this->request($client, '/invoice/template');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
    }

    public function testCreateTemplateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
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
                'numberGenerator' => 'default',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/invoice/template'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);
    }

    public function testCopyTemplateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new InvoiceFixtures();
        $this->importFixture($em, $fixture);

        /** @var InvoiceTemplate $template */
        $template = $em->getRepository(InvoiceTemplate::class)->find(1);

        $this->request($client, '/invoice/template/create/1');
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
        $this->assertEquals($template->getNumberGenerator(), $values['numberGenerator']);
    }

    public function testPrintAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new InvoiceFixtures();
        $this->importFixture($em, $fixture);

        $begin = new \DateTime('first day of this month');
        $end = new \DateTime('last day of this month');
        $fixture = new TimesheetFixtures();
        $fixture
            ->setUser($this->getUserByRole($em, User::ROLE_TEAMLEAD))
            ->setAmount(20)
            ->setStartDate($begin)
        ;
        $this->importFixture($em, $fixture);

        $this->request($client, '/invoice/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $begin->format('Y-m-d') . DateRangeType::DATE_SPACER . $end->format('Y-m-d');

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $client->submit($form, [
            'template' => 1,
            'user' => '',
            'daterange' => $dateRange,
            'customer' => 1,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('div.callout.callout-warning.lead');
        $this->assertEquals(0, $node->count());

        $node = $client->getCrawler()->filter('div.callout.callout-success.lead');
        $this->assertNotEmpty($node->text());
        $this->assertContains('This is a preview of the data that will show up in your invoice document.', $node->text());

        $node = $client->getCrawler()->filter('section.invoice div.table-responsive table.table-striped tbody tr');
        $this->assertEquals(20, $node->count());

        $form = $client->getCrawler()->filter('#invoice-print-form')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/invoice/print'));
        $client->submit($form, [
            'template' => 1,
            'user' => '',
            'daterange' => $dateRange,
            'customer' => 1,
            'project' => 1,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $node = $client->getCrawler()->filter('body');
        $this->assertEquals(1, $node->count());
        $this->assertEquals('invoice_print', $node->getIterator()[0]->getAttribute('class'));
    }

    public function testPrintActionRedirectsToCreateTemplate()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/invoice/print');
        $this->assertIsRedirect($client, '/invoice/template/create');
    }

    public function testPrintActionRedirectsToIndex()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new InvoiceFixtures();
        $this->importFixture($em, $fixture);

        $this->request($client, '/invoice/print');
        $this->assertIsRedirect($client, '/invoice/');
    }

    public function testEditTemplateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new InvoiceFixtures();
        $this->importFixture($em, $fixture);

        $this->request($client, '/invoice/template/1/edit?page=1');
        $form = $client->getCrawler()->filter('form[name=invoice_template_form]')->form();
        $client->submit($form, [
            'invoice_template_form' => [
                'name' => 'Test 2!',
                'title' => 'Test invoice template',
                'company' => 'Company name',
                'renderer' => 'default',
                'calculator' => 'default',
                'numberGenerator' => 'default',
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

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new InvoiceFixtures();
        $this->importFixture($em, $fixture);

        $this->request($client, '/invoice/template/1/delete?page=1');
        $this->assertIsRedirect($client, '/invoice/template/page/1');
        $client->followRedirect();

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $this->assertEquals(0, $em->getRepository(InvoiceTemplate::class)->count([]));
    }
}
