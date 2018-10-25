<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Tests\Controller\ControllerBaseTest;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @coversDefaultClass \App\Controller\Admin\CustomerController
 * @group integration
 */
class CustomerControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/customer/');
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/admin/customer/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/');
        $this->assertHasDataTable($client);
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/create');
        $form = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();

        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        $defaults = $container->getParameter('kimai.defaults')['customer'];

        $editForm = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();
        $this->assertEquals($defaults['country'], $editForm->get('customer_edit_form[country]')->getValue());
        $this->assertEquals($defaults['currency'], $editForm->get('customer_edit_form[currency]')->getValue());
        $this->assertEquals($defaults['timezone'], $editForm->get('customer_edit_form[timezone]')->getValue());

        $client->submit($form, [
            'customer_edit_form' => [
                'name' => 'Test Customer',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/edit');
        $form = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();
        $this->assertFalse($form->has('customer_edit_form[create_more]'));
        $this->assertEquals('Test', $form->get('customer_edit_form[name]')->getValue());
        $client->submit($form, [
            'customer_edit_form' => [
                'name' => 'Test Customer 2'
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->request($client, '/admin/customer/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();
        $this->assertEquals('Test Customer 2', $editForm->get('customer_edit_form[name]')->getValue());
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new CustomerFixtures();
        $fixture->setAmount(1);
        $this->importFixture($em, $fixture);

        $this->request($client, '/admin/customer/2/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->request($client, '/admin/customer/2/delete');
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/'));

        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/customer/2/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntries()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
        $fixture->setAmount(10);
        $this->importFixture($em, $fixture);

        $this->request($client, '/admin/customer/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/customer/1/delete'), $form->getUri());
        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/customer/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testValidationForCreateAction(array $formData, array $validationFields)
    {
        $this->assertFormHasValidationError(
            User::ROLE_ADMIN,
            '/admin/customer/create',
            'form[name=customer_edit_form]',
            $formData,
            $validationFields
        );
    }

    public function getValidationTestData()
    {
        return [
            [
                [
                    'customer_edit_form' => [
                        'name' => '',
                        'visible' => 3,
                        'country' => '00', // TODO why does it not fail?
                        'currency' => '00', // TODO why does it not fail?
                        'timezone' => 'XXX'
                    ]
                ],
                [
                    '#customer_edit_form_name',
                    '#customer_edit_form_visible',
                    //'#customer_edit_form_country',
                    //'#customer_edit_form_currency',
                    '#customer_edit_form_timezone',
                ]
            ],
        ];
    }
}
