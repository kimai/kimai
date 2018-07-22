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
 * @coversDefaultClass \App\Controller\Admin\ActivityController
 * @group integration
 * @group legacy
 */
class ActivityControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/activity/');
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/admin/activity/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/');
        $this->assertHasDataTable($client);
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/create');
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertTrue($form->has('activity_edit_form[create_more]'));
        $this->assertNull($form->get('activity_edit_form[create_more]')->getValue());
        $client->submit($form, [
            'activity_edit_form' => [
                'name' => 'Test 2',
            ]
        ]);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertHasDataTable($client);
    }

    public function testCreateActionWithCreateMore()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/create');
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertTrue($form->has('activity_edit_form[create_more]'));
        $client->submit($form, [
            'activity_edit_form' => [
                'name' => 'Test create more',
                'create_more' => true,
                // TODO select random project
            ]
        ]);
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertTrue($form->has('activity_edit_form[create_more]'));
        $this->assertEquals(1, $form->get('activity_edit_form[create_more]')->getValue());
        // TODO test that project is pre-selected
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/1/edit');
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertFalse($form->has('activity_edit_form[create_more]'));
        $this->assertEquals('Test', $form->get('activity_edit_form[name]')->getValue());
        $client->submit($form, [
            'activity_edit_form' => ['name' => 'Test 2']
        ]);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->request($client, '/admin/activity/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('activity_edit_form[name]')->getValue());
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testValidationForCreateAction(array $formData, array $validationFields)
    {
        $this->assertFormHasValidationError(
            User::ROLE_ADMIN,
            '/admin/activity/create',
            'form[name=activity_edit_form]',
            $formData,
            $validationFields
        );
    }

    public function getValidationTestData()
    {
        return [
            [
                [
                    'activity_edit_form' => [
                        'name' => '',
                        'project' => 0,
                        'visible' => 3,
                    ]
                ],
                [
                    '#activity_edit_form_name',
                    '#activity_edit_form_project',
                    '#activity_edit_form_visible',
                ]
            ],
        ];
    }
}
