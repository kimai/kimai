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
use App\Tests\DataFixtures\InvoiceFixtures;

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
        $this->assertContains('Before you can create an invoice, you have to select at least a customer filter.', $node->text());
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
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

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
