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
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;

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
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
    }

    public function testCreateActionWithCreateMore()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new ProjectFixtures();
        $fixture->setAmount(10);
        $this->importFixture($em, $fixture);

        $this->assertAccessIsGranted($client, '/admin/activity/create');
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertTrue($form->has('activity_edit_form[create_more]'));

        /** @var \Symfony\Component\DomCrawler\Field\ChoiceFormField $project */
        $project = $form->get('activity_edit_form[project]');
        $options = $project->availableOptionValues();
        $selectedProject = $options[array_rand($options)];

        $client->submit($form, [
            'activity_edit_form' => [
                'name' => 'Test create more',
                'create_more' => true,
                'project' => $selectedProject,
            ]
        ]);
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertTrue($form->has('activity_edit_form[create_more]'));
        $this->assertEquals(1, $form->get('activity_edit_form[create_more]')->getValue());
        $this->assertEquals($selectedProject, $form->get('activity_edit_form[project]')->getValue());
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
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->request($client, '/admin/activity/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('activity_edit_form[name]')->getValue());
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/admin/activity/1/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->request($client, '/admin/activity/1/delete');
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/activity/1/edit');
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

        $this->request($client, '/admin/activity/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/activity/1/delete'), $form->getUri());
        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/activity/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
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
