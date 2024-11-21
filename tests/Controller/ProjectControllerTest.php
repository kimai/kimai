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
use App\Entity\ActivityRate;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\ProjectRate;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TeamFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\ProjectTestMetaFieldSubscriberMock;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class ProjectControllerTest extends ControllerBaseTest
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/admin/project/');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/admin/project/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/admin/project/');
        $this->assertHasDataTable($client);

        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/project/export'),
        ]);
    }

    public function testIndexActionAsSuperAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/');
        $this->assertHasDataTable($client);

        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/project/export'),
            'create modal-ajax-form' => $this->createUrl('/admin/project/create'),
        ]);
    }

    public function testIndexActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new ProjectFixtures();
        $fixture->setAmount(5);
        $fixture->setCallback(function (Project $project) {
            $project->setVisible(true);
            $project->setComment('I am a foobar with tralalalala some more content');
            $project->setMetaField((new ProjectMeta())->setName('location')->setValue('homeoffice'));
            $project->setMetaField((new ProjectMeta())->setName('feature')->setValue('timetracking'));
        });
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/project/');

        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/project/export'),
            'create modal-ajax-form' => $this->createUrl('/admin/project/create'),
        ]);

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'searchTerm' => 'feature:timetracking foo',
            'visibility' => 1,
            'customers' => [1],
            'size' => 50,
            'page' => 1,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_project_admin', 5);
    }

    public function testExportIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/admin/project/export');
    }

    public function testExportAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/admin/project/export');
        $this->assertExcelExportResponse($client, 'kimai-projects_');
    }

    public function testExportActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new ProjectFixtures();
        $fixture->setAmount(5);
        $fixture->setCallback(function (Project $project) {
            $project->setVisible(true);
            $project->setComment('I am a foobar with tralalalala some more content');
            $project->setMetaField((new ProjectMeta())->setName('location')->setValue('homeoffice'));
            $project->setMetaField((new ProjectMeta())->setName('feature')->setValue('timetracking'));
        });
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/project/');

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/admin/project/export'));
        $client->submit($form, [
            'searchTerm' => 'feature:timetracking foo',
            'visibility' => 1,
            'customers' => [1],
            'size' => 50,
            'page' => 1,
        ]);

        $this->assertExcelExportResponse($client, 'kimai-projects_');
    }

    public function testDetailsAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $project = $em->getRepository(Project::class)->find(1);

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setProjects([$project]);
        $fixture->setUser($this->getUserByRole(User::ROLE_ADMIN));
        $this->importFixture($fixture);

        $project = $em->getRepository(Project::class)->find(1);
        $fixture = new ActivityFixtures();
        $fixture->setAmount(6); // to trigger a second page
        $fixture->setProjects([$project]);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/project/1/details');
        $this->assertDetailsPage($client);
    }

    private function assertDetailsPage(HttpKernelBrowser $client)
    {
        self::assertHasProgressbar($client);

        $node = $client->getCrawler()->filter('div.card#project_details_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#activity_list_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#time_budget_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#budget_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#team_listing_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#comments_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-actions a.btn');
        self::assertEquals(2, $node->count());
        $node = $client->getCrawler()->filter('div.card#project_rates_box');
        self::assertEquals(1, $node->count());
    }

    public function testAddRateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAddRate($client, 123.45, 1);
    }

    public function assertAddRate(HttpKernelBrowser $client, $rate, $projectId): void
    {
        $this->assertAccessIsGranted($client, '/admin/project/' . $projectId . '/rate');
        $form = $client->getCrawler()->filter('form[name=project_rate_form]')->form();
        $client->submit($form, [
            'project_rate_form' => [
                'rate' => $rate,
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/' . $projectId . '/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#project_rates_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#project_rates_box table.dataTable tbody tr:not(.summary)');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString($rate, $node->text(null, true));
    }

    public function testDuplicateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $project = $em->find(Project::class, 1);
        $project->setMetaField((new ProjectMeta())->setName('foo')->setValue('bar'));
        $project->setEnd(new \DateTime());
        $em->persist($project);
        $team = new Team('project 1');
        $team->addTeamlead($this->getUserByRole(User::ROLE_ADMIN));
        $team->addProject($project);
        $em->persist($team);
        $rate = new ProjectRate();
        $rate->setProject($project);
        $rate->setRate(123.45);
        $em->persist($rate);
        $activity = new Activity();
        $activity->setName('blub');
        $activity->setProject($project);
        $activity->setMetaField((new ActivityMeta())->setName('blub')->setValue('blab'));
        $em->persist($activity);
        $rate = new ActivityRate();
        $rate->setActivity($activity);
        $rate->setRate(123.45);
        $em->persist($rate);
        $em->flush();

        $token = $this->getCsrfToken($client, 'project.duplicate');

        $this->request($client, '/admin/project/1/duplicate/' . $token);
        $this->assertIsRedirect($client, '/details');
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#project_rates_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#project_rates_box table.dataTable tbody tr:not(.summary)');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString('123.45', $node->text(null, true));
    }

    public function testDuplicateActionWithInvalidCsrf(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $project = $em->find(Project::class, 1);
        $project->setMetaField((new ProjectMeta())->setName('foo')->setValue('bar'));
        $project->setEnd(new \DateTime());
        $em->persist($project);
        $activity = new Activity();
        $activity->setName('blub');
        $activity->setProject($project);
        $activity->setMetaField((new ActivityMeta())->setName('blub')->setValue('blab'));
        $em->persist($activity);
        $em->flush();

        $this->assertInvalidCsrfToken($client, '/admin/project/1/duplicate/rsetdzfukgli78t6r5uedtjfzkugl', $this->createUrl('/admin/project/1/details'));
    }

    public function testAddCommentAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/details');
        $form = $client->getCrawler()->filter('form[name=project_comment_form]')->form();
        $client->submit($form, [
            'project_comment_form' => [
                'message' => 'A beautiful and long comment **with some** markdown formatting',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body');
        self::assertStringContainsString('A beautiful and long comment **with some** markdown formatting', $node->html());

        $this->setSystemConfiguration('timesheet.markdown_content', true);
        $this->assertAccessIsGranted($client, '/admin/project/1/details');
        $node = $client->getCrawler()->filter('div.card#comments_box .direct-chat-text');
        self::assertStringContainsString('<p>A beautiful and long comment <strong>with some</strong> markdown formatting</p>', $node->html());
    }

    public function testDeleteCommentAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/details');
        $form = $client->getCrawler()->filter('form[name=project_comment_form]')->form();
        $client->submit($form, [
            'project_comment_form' => [
                'message' => 'Foo bar blub',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body');
        self::assertStringContainsString('Foo bar blub', $node->html());
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body a.delete-comment-link');

        $this->request($client, $node->attr('href'));
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body');
        self::assertStringContainsString('There were no comments posted yet', $node->html());
    }

    public function testPinCommentAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/details');
        $form = $client->getCrawler()->filter('form[name=project_comment_form]')->form();
        $client->submit($form, [
            'project_comment_form' => [
                'message' => 'Foo bar blub',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body');
        self::assertStringContainsString('Foo bar blub', $node->html());
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body a.pin-comment-link.active');
        self::assertEquals(0, $node->count());

        $node = $client->getCrawler()->filter('div.card#comments_box .card-body a.pin-comment-link');
        self::assertEquals(1, $node->count());
        $this->request($client, $node->attr('href'));
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body a.pin-comment-link.active');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString('/admin/project/', $node->attr('href'));
        self::assertStringContainsString('/comment_pin/', $node->attr('href'));
    }

    public function testCreateDefaultTeamAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/details');
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-body');
        self::assertStringContainsString('Visible to everyone, as no team was assigned yet.', $node->text(null, true));

        $this->request($client, '/admin/project/1/create_team');
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-title');
        self::assertStringContainsString('Only visible to the following teams and all admins.', $node->text(null, true));
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-body table tbody tr');
        self::assertEquals(1, $node->count());
    }

    public function testActivitiesAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/activities/1');
        $node = $client->getCrawler()->filter('div.card#activity_list_box .card-actions ul.pagination li');
        self::assertEquals(0, $node->count());
        $node = $client->getCrawler()->filter('div.card#activity_list_box .card-actions a.modal-ajax-form.open-edit');
        self::assertEquals(1, $node->count());

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $project = $em->getRepository(Project::class)->find(1);
        $fixture = new ActivityFixtures();
        $fixture->setAmount(9); // to trigger a second page (every third activity is hidden)
        $fixture->setProjects([$project]);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/project/1/activities/1');

        $node = $client->getCrawler()->filter('div.card#activity_list_box .card-footer ul.pagination li');
        self::assertEquals(4, $node->count());

        $node = $client->getCrawler()->filter('div.card#activity_list_box .card-body table tbody tr');
        self::assertEquals(5, $node->count());
    }

    public function testCreateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/create');
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $client->submit($form, [
            'project_edit_form' => [
                'name' => 'Test 2',
                'customer' => 1,
            ]
        ]);

        $location = $this->assertIsModalRedirect($client, '/details');
        $this->requestPure($client, $location);

        $this->assertDetailsPage($client);
        $this->assertHasFlashSuccess($client);
    }

    public function testCreateActionShowsMetaFields(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new ProjectTestMetaFieldSubscriberMock());
        $this->assertAccessIsGranted($client, '/admin/project/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertTrue($form->has('project_edit_form[metaFields][metatestmock][value]'));
        $this->assertTrue($form->has('project_edit_form[metaFields][foobar][value]'));
        $this->assertFalse($form->has('project_edit_form[metaFields][0][value]'));
    }

    public function testEditAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/edit');
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertEquals('Test', $form->get('project_edit_form[name]')->getValue());
        $client->submit($form, [
            'project_edit_form' => ['name' => 'Test 2']
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $this->request($client, '/admin/project/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('project_edit_form[name]')->getValue());
    }

    public function testTeamPermissionAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $em = $this->getEntityManager();

        /** @var Project $project */
        $project = $em->getRepository(Project::class)->find(1);
        self::assertEquals(0, $project->getTeams()->count());

        $fixture = new TeamFixtures();
        $fixture->setAmount(2);
        $fixture->setAddCustomer(false);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/project/1/permissions');
        $form = $client->getCrawler()->filter('form[name=project_team_permission_form]')->form();
        /** @var ChoiceFormField $team1 */
        $team1 = $form->get('project_team_permission_form[teams][0]');
        $team1->tick();
        /** @var ChoiceFormField $team2 */
        $team2 = $form->get('project_team_permission_form[teams][1]');
        $team2->tick();

        $client->submit($form);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));

        /** @var Project $project */
        $project = $em->getRepository(Project::class)->find(1);
        self::assertEquals(2, $project->getTeams()->count());
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new ProjectFixtures();
        $fixture->setAmount(1);
        /** @var Project[] $projects */
        $projects = $this->importFixture($fixture);
        $id = $projects[0]->getId();

        $this->request($client, '/admin/project/' . $id . '/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->request($client, '/admin/project/' . $id . '/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/project/' . $id . '/delete'), $form->getUri());
        $client->submit($form);

        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/project/' . $id . '/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntries(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

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

        $this->request($client, '/admin/project/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/project/1/delete'), $form->getUri());
        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/admin/project/'));
        $client->followRedirect();
        $this->assertHasFlashDeleteSuccess($client);
        $this->assertHasNoEntriesWithFilter($client);

        $em->clear();
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(0, \count($timesheets));

        $this->request($client, '/admin/project/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntriesAndReplacement(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $this->getEntityManager();
        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setAmount(10);
        $this->importFixture($fixture);
        $fixture = new ProjectFixtures();
        $fixture->setAmount(1)->setIsVisible(true);
        $projects = $this->importFixture($fixture);
        $id = $projects[0]->getId();

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(10, \count($timesheets));

        /** @var Timesheet $entry */
        foreach ($timesheets as $entry) {
            $this->assertEquals(1, $entry->getProject()->getId());
        }

        $this->request($client, '/admin/project/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/project/1/delete'), $form->getUri());
        $client->submit($form, [
            'form' => [
                'project' => $id
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/project/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(10, \count($timesheets));

        /** @var Timesheet $entry */
        foreach ($timesheets as $entry) {
            $this->assertEquals($id, $entry->getProject()->getId());
        }

        $this->request($client, '/admin/project/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testValidationForCreateAction(array $formData, array $validationFields): void
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
                    ]
                ],
                [
                    '#project_edit_form_name',
                    '#project_edit_form_customer',
                ]
            ],
        ];
    }
}
