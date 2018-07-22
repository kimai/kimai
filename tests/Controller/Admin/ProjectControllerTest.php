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
                'name' => 'Test 2'
            ]
        ]);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertHasDataTable($client);
    }

    public function testCreateActionWithCreateMore()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/create');
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertTrue($form->has('project_edit_form[create_more]'));
        $client->submit($form, [
            'project_edit_form' => [
                'name' => 'Test create more',
                'create_more' => true
            ]
        ]);
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertTrue($form->has('project_edit_form[create_more]'));
        $this->assertEquals(1, $form->get('project_edit_form[create_more]')->getValue());
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
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->request($client, '/admin/project/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('project_edit_form[name]')->getValue());
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
