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
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @coversDefaultClass \App\Controller\Admin\ProjectController
 * @group integration
 * @group legacy
 */
class ProjectControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/project/');
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/admin/project/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/');
        $this->assertHasDataTable($client);
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/create');
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertTrue($form->has('project_edit_form[create_more]'));
        $this->assertNull($form->get('project_edit_form[create_more]')->getValue());
        $client->submit($form, [
            'project_edit_form' => [
                'name' => 'Test 2',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);
    }

    public function testCreateActionWithCreateMore()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new CustomerFixtures();
        $fixture->setAmount(10);
        $this->importFixture($em, $fixture);

        $this->assertAccessIsGranted($client, '/admin/project/create');
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertTrue($form->has('project_edit_form[create_more]'));

        /** @var \Symfony\Component\DomCrawler\Field\ChoiceFormField $customer */
        $customer = $form->get('project_edit_form[customer]');
        $options = $customer->availableOptionValues();
        $selectedCustomer = $options[array_rand($options)];

        $client->submit($form, [
            'project_edit_form' => [
                'name' => 'Test create more',
                'create_more' => true,
                'customer' => $selectedCustomer
            ]
        ]);
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertTrue($form->has('project_edit_form[create_more]'));
        $this->assertEquals(1, $form->get('project_edit_form[create_more]')->getValue());
        $this->assertEquals($selectedCustomer, $form->get('project_edit_form[customer]')->getValue());
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/edit');
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertFalse($form->has('project_edit_form[create_more]'));
        $this->assertEquals('Test', $form->get('project_edit_form[name]')->getValue());
        $client->submit($form, [
            'project_edit_form' => ['name' => 'Test 2']
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->request($client, '/admin/project/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('project_edit_form[name]')->getValue());
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new ProjectFixtures();
        $fixture->setAmount(1);
        $this->importFixture($em, $fixture);

        $this->request($client, '/admin/project/2/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->request($client, '/admin/project/2/delete');
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/project/2/edit');
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

        $this->request($client, '/admin/project/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/project/1/delete'), $form->getUri());
        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/admin/project/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/project/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testValidationForCreateAction(array $formData, array $validationFields)
    {
        $this->assertFormHasValidationError(
            User::ROLE_ADMIN,
            '/admin/project/create',
            'form[name=project_edit_form]',
            $formData,
            $validationFields
        );
    }

    public function getValidationTestData()
    {
        return [
            [
                [
                    'project_edit_form' => [
                        'name' => '',
                        'customer' => 0,
                        'visible' => 3,
                    ]
                ],
                [
                    '#project_edit_form_name',
                    '#project_edit_form_customer',
                    '#project_edit_form_visible',
                ]
            ],
        ];
    }
}
