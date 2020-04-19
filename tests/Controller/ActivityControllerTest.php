<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\ActivityTestMetaFieldSubscriberMock;
use Doctrine\ORM\EntityManager;

/**
 * @group integration
 */
class ActivityControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/activity/');
    }

    public function testIsSecureForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/admin/activity/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/admin/activity/');
        $this->assertHasDataTable($client);
    }

    public function testIndexActionWithSearchTermQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new ActivityFixtures();
        $fixture->setAmount(5);
        $fixture->setCallback(function (Activity $activity) {
            $activity->setVisible(true);
            $activity->setComment('I am a foobar with tralalalala some more content');
            $activity->setMetaField((new ActivityMeta())->setName('location')->setValue('homeoffice'));
            $activity->setMetaField((new ActivityMeta())->setName('feature')->setValue('timetracking'));
        });
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/activity/');

        $form = $client->getCrawler()->filter('form.header-search')->form();
        $client->submit($form, [
            'searchTerm' => 'feature:timetracking foo',
            'visibility' => 1,
            'pageSize' => 50,
            'customers' => [1],
            'projects' => [1],
            'page' => 1,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_activity_admin', 5);
    }

    public function testDetailsAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setActivities($em->getRepository(Activity::class)->findAll());
        $fixture->setUser($this->getUserByRole(User::ROLE_ADMIN));
        $this->importFixture($fixture);

        $project = $em->getRepository(Project::class)->find(1);
        $fixture = new ActivityFixtures();
        $fixture->setAmount(6); // to trigger a second page
        $fixture->setProjects([$project]);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/activity/1/details');
        self::assertHasProgressbar($client);

        $node = $client->getCrawler()->filter('div.box#activity_details_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#budget_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#activity_rates_box');
        self::assertEquals(1, $node->count());
    }

    public function testAddRateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/1/rate');
        $form = $client->getCrawler()->filter('form[name=activity_rate_form]')->form();
        $client->submit($form, [
            'activity_rate_form' => [
                'user' => null,
                'rate' => 123.45,
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.box#activity_rates_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#activity_rates_box table.dataTable tbody tr:not(.summary)');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString('123.45', $node->text(null, true));
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/create');
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertTrue($form->has('activity_edit_form[create_more]'));
        $this->assertFalse($form->get('activity_edit_form[create_more]')->hasValue());
        $client->submit($form, [
            'activity_edit_form' => [
                'name' => 'An AcTiVitY Name',
                'project' => '1',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);

        $this->request($client, '/admin/activity/2/edit');
        $editForm = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('An AcTiVitY Name', $editForm->get('activity_edit_form[name]')->getValue());
        // make sure customer and project are pre-selected for none global activities
        $this->assertEquals('1', $editForm->get('activity_edit_form[project]')->getValue());
        $this->assertEquals('1', $editForm->get('activity_edit_form[customer]')->getValue());
    }

    public function testCreateActionShowsMetaFields()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        static::$kernel->getContainer()->get('event_dispatcher')->addSubscriber(new ActivityTestMetaFieldSubscriberMock());
        $this->assertAccessIsGranted($client, '/admin/activity/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertTrue($form->has('activity_edit_form[metaFields][0][value]'));
        $this->assertFalse($form->has('activity_edit_form[metaFields][1][value]'));
    }

    public function testCreateActionWithCreateMore()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new ProjectFixtures();
        $fixture->setAmount(10);
        $this->importFixture($fixture);

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
        $this->assertTrue($form->get('activity_edit_form[create_more]')->hasValue());
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
            'activity_edit_form' => ['name' => 'Test 2', 'customer' => 1, 'project' => '1']
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->request($client, '/admin/activity/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('activity_edit_form[name]')->getValue());
        $this->assertEquals('1', $editForm->get('activity_edit_form[customer]')->getValue());
        $this->assertEquals('1', $editForm->get('activity_edit_form[project]')->getValue());
    }

    public function testEditActionForGlobalActivity()
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
        // make sure no customer or project is pre-selected for global activities
        $this->assertEquals('', $editForm->get('activity_edit_form[customer]')->getValue());
        $this->assertEquals('', $editForm->get('activity_edit_form[project]')->getValue());
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/admin/activity/1/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->request($client, '/admin/activity/1/delete');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/activity/1/delete'), $form->getUri());
        $client->submit($form);

        $client->followRedirect();
        $this->assertHasFlashDeleteSuccess($client);
        $this->assertHasNoEntriesWithFilter($client);

        $this->request($client, '/admin/activity/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntries()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setAmount(10);
        $this->importFixture($fixture);

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(10, \count($timesheets));

        /** @var Timesheet $entry */
        foreach ($timesheets as $entry) {
            $this->assertEquals(1, $entry->getActivity()->getId());
        }

        $this->request($client, '/admin/activity/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/activity/1/delete'), $form->getUri());
        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/'));
        $client->followRedirect();
        $this->assertHasFlashDeleteSuccess($client);
        $this->assertHasNoEntriesWithFilter($client);

        // SQLIte does not necessarly support onCascade delete, so these timesheet will stay after deletion
        // $em->clear();
        // $timesheets = $em->getRepository(Timesheet::class)->findAll();
        // $this->assertEquals(0, count($timesheets));

        $this->request($client, '/admin/activity/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntriesAndReplacement()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setAmount(10);
        $this->importFixture($fixture);
        $fixture = new ActivityFixtures();
        $fixture->setAmount(1)->setIsGlobal(true)->setIsVisible(true);
        $this->importFixture($fixture);

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(10, \count($timesheets));

        /** @var Timesheet $entry */
        foreach ($timesheets as $entry) {
            $this->assertEquals(1, $entry->getActivity()->getId());
        }

        $this->request($client, '/admin/activity/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/activity/1/delete'), $form->getUri());
        $client->submit($form, [
            'form' => [
                'activity' => 2
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(10, \count($timesheets));

        /** @var Timesheet $entry */
        foreach ($timesheets as $entry) {
            $this->assertEquals(2, $entry->getActivity()->getId());
        }

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
                    ]
                ],
                [
                    '#activity_edit_form_name',
                    '#activity_edit_form_project',
                ]
            ],
        ];
    }
}
