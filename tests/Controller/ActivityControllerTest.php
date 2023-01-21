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
use App\Tests\DataFixtures\TeamFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\ActivityTestMetaFieldSubscriberMock;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

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

        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/activity/export'),
            'create modal-ajax-form' => $this->createUrl('/admin/activity/create'),
        ]);
    }

    public function testIndexActionAsSuperAdmin()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/');
        $this->assertHasDataTable($client);

        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/activity/export'),
            'create modal-ajax-form' => $this->createUrl('/admin/activity/create'),
        ]);
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

        $form = $client->getCrawler()->filter('form.searchform')->form();
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

    public function testExportIsSecureForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/admin/activity/export');
    }

    public function testExportAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/admin/activity/export');
        $this->assertExcelExportResponse($client, 'kimai-activities_');
    }

    public function testExportActionWithSearchTermQuery()
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

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/admin/activity/export'));
        $client->submit($form, [
            'searchTerm' => 'feature:timetracking foo',
            'visibility' => 1,
            'pageSize' => 50,
            'customers' => [1],
            'projects' => [1],
            'page' => 1,
        ]);

        $this->assertExcelExportResponse($client, 'kimai-activities_');
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

        $this->assertDetailsPage($client);
    }

    private function assertDetailsPage(HttpKernelBrowser $client)
    {
        self::assertHasProgressbar($client);

        $node = $client->getCrawler()->filter('div.card#activity_details_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#time_budget_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#budget_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#activity_rates_box');
        self::assertEquals(1, $node->count());
    }

    public function testAddRateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/1/rate');
        $form = $client->getCrawler()->filter('form[name=activity_rate_form]')->form();
        $client->submit($form, [
            'activity_rate_form' => [
                'rate' => 123.45,
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#activity_rates_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#activity_rates_box table.dataTable tbody tr:not(.summary)');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString('123.45', $node->text(null, true));
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/create');
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $client->submit($form, [
            'activity_edit_form' => [
                'name' => 'An AcTiVitY Name',
                'project' => '1',
            ]
        ]);

        $location = $this->assertIsModalRedirect($client, '/details');
        $this->requestPure($client, $location);

        $this->assertDetailsPage($client);
        $this->assertHasFlashSuccess($client);

        $activities = $this->getEntityManager()->getRepository(Activity::class)->findAll();
        $activity = array_pop($activities);
        $id = $activity->getId();

        $this->request($client, '/admin/activity/' . $id . '/edit');
        $editForm = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('An AcTiVitY Name', $editForm->get('activity_edit_form[name]')->getValue());
    }

    public function testCreateActionShowsMetaFields()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        self::getContainer()->get('event_dispatcher')->addSubscriber(new ActivityTestMetaFieldSubscriberMock());
        $this->assertAccessIsGranted($client, '/admin/activity/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertTrue($form->has('activity_edit_form[metaFields][metatestmock][value]'));
        $this->assertTrue($form->has('activity_edit_form[metaFields][foobar][value]'));
        $this->assertFalse($form->has('activity_edit_form[metaFields][0][value]'));
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/1/edit');
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('Test', $form->get('activity_edit_form[name]')->getValue());
        $client->submit($form, [
            'activity_edit_form' => ['name' => 'Test 2']
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/1/details'));
        $client->followRedirect();
        $this->request($client, '/admin/activity/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('activity_edit_form[name]')->getValue());
    }

    public function testEditActionForGlobalActivity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/1/edit');
        $form = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('Test', $form->get('activity_edit_form[name]')->getValue());
        $client->submit($form, [
            'activity_edit_form' => ['name' => 'Test 2']
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/1/details'));
        $client->followRedirect();
        $this->request($client, '/admin/activity/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=activity_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('activity_edit_form[name]')->getValue());
    }

    public function testTeamPermissionAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $em = $this->getEntityManager();

        /** @var Activity $activity */
        $activity = $em->getRepository(Activity::class)->findAll()[0];
        self::assertEquals(0, $activity->getTeams()->count());
        $id = $activity->getId();

        $fixture = new TeamFixtures();
        $fixture->setAmount(2);
        $fixture->setAddCustomer(false);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/activity/' . $id . '/permissions');
        $form = $client->getCrawler()->filter('form[name=activity_team_permission_form]')->form();
        /** @var ChoiceFormField $team1 */
        $team1 = $form->get('activity_team_permission_form[teams][0]');
        $team1->tick();
        /** @var ChoiceFormField $team2 */
        $team2 = $form->get('activity_team_permission_form[teams][1]');
        $team2->tick();

        $client->submit($form);
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/' . $id . '/details'));

        /** @var Activity $activity */
        $activity = $em->getRepository(Activity::class)->find($id);
        self::assertEquals(2, $activity->getTeams()->count());
    }

    public function testCreateDefaultTeamAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/1/details');
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-body');
        self::assertStringContainsString('Visible to everyone, as no team was assigned yet.', $node->text());

        $this->request($client, '/admin/activity/1/create_team');
        $this->assertIsRedirect($client, $this->createUrl('/admin/activity/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-title');
        self::assertStringContainsString('Only visible to the following teams and all admins.', $node->text());
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-body table tbody tr');
        self::assertEquals(1, $node->count());
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

        $em->clear();
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(0, \count($timesheets));

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
        $activities = $this->importFixture($fixture);
        $activity = $activities[0];
        $id = $activity->getId();

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
                'activity' => $id
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
            $this->assertEquals($id, $entry->getActivity()->getId());
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
