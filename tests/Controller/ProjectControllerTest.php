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
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TeamFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\ProjectTestMetaFieldSubscriberMock;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class ProjectControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/project/');
    }

    public function testIsSecureForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/admin/project/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/admin/project/');
        $this->assertHasDataTable($client);
    }

    public function testIndexActionWithSearchTermQuery()
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

        $form = $client->getCrawler()->filter('form.header-search')->form();
        $client->submit($form, [
            'searchTerm' => 'feature:timetracking foo',
            'visibility' => 1,
            'customers' => [1],
            'pageSize' => 50,
            'page' => 1,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_project_admin', 5);
    }

    public function testDetailsAction()
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
        self::assertHasProgressbar($client);

        $node = $client->getCrawler()->filter('div.box#project_details_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#activity_list_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#budget_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#team_listing_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#comments_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#team_listing_box a.btn-box-tool');
        self::assertEquals(2, $node->count());
        $node = $client->getCrawler()->filter('div.box#project_rates_box');
        self::assertEquals(1, $node->count());
    }

    public function testAddRateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAddRate($client, 123.45, 1);
    }

    protected function assertAddRate(HttpKernelBrowser $client, $rate, $projectId)
    {
        $this->assertAccessIsGranted($client, '/admin/project/' . $projectId . '/rate');
        $form = $client->getCrawler()->filter('form[name=project_rate_form]')->form();
        $client->submit($form, [
            'project_rate_form' => [
                'user' => null,
                'rate' => $rate,
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/' . $projectId . '/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.box#project_rates_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#project_rates_box table.dataTable tbody tr:not(.summary)');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString($rate, $node->text(null, true));
    }

    public function testDuplicateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $project = $em->find(Project::class, 1);
        $project->setMetaField((new ProjectMeta())->setName('foo')->setValue('bar'));
        $project->setEnd(new \DateTime());
        $em->persist($project);
        $team = new Team();
        $team->setTeamLead($this->getUserByRole(User::ROLE_ADMIN));
        $team->addProject($project);
        $team->setName('project 1');
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

        $this->request($client, '/admin/project/1/duplicate');
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/2/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.box#project_rates_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.box#project_rates_box table.dataTable tbody tr:not(.summary)');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString('123.45', $node->text(null, true));
    }

    public function testAddCommentAction()
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
        $node = $client->getCrawler()->filter('div.box#comments_box div.box-comments');
        self::assertStringContainsString('<p>A beautiful and long comment <strong>with some</strong> markdown formatting</p>', $node->html());
    }

    public function testDeleteCommentAction()
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
        $node = $client->getCrawler()->filter('div.box#comments_box div.box-comments');
        self::assertStringContainsString('Foo bar blub', $node->html());
        $node = $client->getCrawler()->filter('div.box#comments_box .box-comment a.confirmation-link');
        self::assertEquals($this->createUrl('/admin/project/1/comment_delete'), $node->attr('href'));

        $this->request($client, '/admin/project/1/comment_delete');
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.box#comments_box div.box-comments');
        self::assertStringContainsString('There were no comments posted yet', $node->html());
    }

    public function testPinCommentAction()
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
        $node = $client->getCrawler()->filter('div.box#comments_box div.box-comments');
        self::assertStringContainsString('Foo bar blub', $node->html());
        $node = $client->getCrawler()->filter('div.box#comments_box .box-comment a.btn.active');
        self::assertEquals(0, $node->count());

        $this->request($client, '/admin/project/1/comment_pin');
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.box#comments_box .box-comment a.btn.active');
        self::assertEquals(1, $node->count());
        self::assertEquals($this->createUrl('/admin/project/1/comment_pin'), $node->attr('href'));
    }

    public function testCreateDefaultTeamAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/details');
        $node = $client->getCrawler()->filter('div.box#team_listing_box .box-body');
        self::assertStringContainsString('Visible to everyone, as no team was assigned yet.', $node->text(null, true));

        $this->request($client, '/admin/project/1/create_team');
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.box#team_listing_box .box-body');
        self::assertStringContainsString('Only visible to the following teams and all admins.', $node->text(null, true));
        $node = $client->getCrawler()->filter('div.box#team_listing_box .box-body table tbody tr');
        self::assertEquals(1, $node->count());
    }

    public function testActivitiesAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/1/activities/1');
        self::assertEquals('', $client->getResponse()->getContent());

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $project = $em->getRepository(Project::class)->find(1);
        $fixture = new ActivityFixtures();
        $fixture->setAmount(9); // to trigger a second page (every third activity is hidden)
        $fixture->setProjects([$project]);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/project/1/activities/1');

        $node = $client->getCrawler()->filter('div.box#activity_list_box .box-tools ul.pagination li');
        self::assertEquals(4, $node->count());

        $node = $client->getCrawler()->filter('div.box#activity_list_box .box-body table tbody tr');
        self::assertEquals(5, $node->count());
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/project/create');
        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertTrue($form->has('project_edit_form[create_more]'));
        $this->assertFalse($form->get('project_edit_form[create_more]')->hasValue());
        $client->submit($form, [
            'project_edit_form' => [
                'name' => 'Test 2',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/2/details'));
        $client->followRedirect();
        $this->assertHasFlashSuccess($client);
    }

    public function testCreateActionShowsMetaFields()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        static::$kernel->getContainer()->get('event_dispatcher')->addSubscriber(new ProjectTestMetaFieldSubscriberMock());
        $this->assertAccessIsGranted($client, '/admin/project/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertTrue($form->has('project_edit_form[metaFields][0][value]'));
        $this->assertFalse($form->has('project_edit_form[metaFields][1][value]'));
    }

    public function testCreateActionWithCreateMore()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new CustomerFixtures();
        $fixture->setAmount(10);
        $this->importFixture($fixture);

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
        $this->assertTrue($form->get('project_edit_form[create_more]')->hasValue());
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
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/1/details'));
        $client->followRedirect();
        $this->request($client, '/admin/project/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=project_edit_form]')->form();
        $this->assertEquals('Test 2', $editForm->get('project_edit_form[name]')->getValue());
    }

    public function testTeamPermissionAction()
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
        $this->assertIsRedirect($client, $this->createUrl('/admin/project/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);

        /** @var Project $project */
        $project = $em->getRepository(Project::class)->find(1);
        self::assertEquals(2, $project->getTeams()->count());
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new ProjectFixtures();
        $fixture->setAmount(1);
        $this->importFixture($fixture);

        $this->request($client, '/admin/project/2/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->request($client, '/admin/project/2/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/project/2/delete'), $form->getUri());
        $client->submit($form);

        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/project/2/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntries()
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

        // SQLIte does not necessarly support onCascade delete, so these timesheet will stay after deletion
        // $em->clear();
        // $timesheets = $em->getRepository(Timesheet::class)->findAll();
        // $this->assertEquals(0, count($timesheets));

        $this->request($client, '/admin/project/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntriesAndReplacement()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $em = $this->getEntityManager();
        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setAmount(10);
        $this->importFixture($fixture);
        $fixture = new ProjectFixtures();
        $fixture->setAmount(1)->setIsVisible(true);
        $this->importFixture($fixture);

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
                'project' => 2
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
            $this->assertEquals(2, $entry->getProject()->getId());
        }

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
