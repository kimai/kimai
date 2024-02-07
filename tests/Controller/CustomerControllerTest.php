<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Customer;
use App\Entity\CustomerComment;
use App\Entity\CustomerMeta;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TeamFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\CustomerTestMetaFieldSubscriberMock;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * @group integration
 */
class CustomerControllerTest extends ControllerBaseTest
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/admin/customer/');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/admin/customer/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/admin/customer/');
        $this->assertHasDataTable($client);

        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/customer/export'),
        ]);
    }

    public function testIndexActionAsSuperAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/');
        $this->assertHasDataTable($client);

        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/customer/export'),
            'create modal-ajax-form' => $this->createUrl('/admin/customer/create'),
        ]);
    }

    public function testIndexActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new CustomerFixtures();
        $fixture->setAmount(5);
        $fixture->setCallback(function (Customer $customer) {
            $customer->setVisible(true);
            $customer->setComment('I am a foobar with tralalalala some more content');
            $customer->setMetaField((new CustomerMeta())->setName('location')->setValue('homeoffice'));
            $customer->setMetaField((new CustomerMeta())->setName('feature')->setValue('timetracking'));
        });
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/customer/');

        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/customer/export'),
            'create modal-ajax-form' => $this->createUrl('/admin/customer/create'),
        ]);

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'searchTerm' => 'feature:timetracking foo',
            'visibility' => 1,
            'pageSize' => 50,
            'page' => 1,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_customer_admin', 5);
    }

    public function testExportIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/admin/customer/export');
    }

    public function testExportAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/admin/customer/export');
        $this->assertExcelExportResponse($client, 'kimai-customers_');
    }

    public function testExportActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/admin/customer/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/admin/customer/export'));
        $client->submit($form, [
            'searchTerm' => 'feature:timetracking foo',
            'visibility' => 1,
            'pageSize' => 50,
            'page' => 1,
        ]);

        $this->assertExcelExportResponse($client, 'kimai-customers_');
    }

    public function testDetailsAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/details');
        $this->assertDetailsPage($client);
    }

    private function assertDetailsPage(HttpKernelBrowser $client)
    {
        self::assertHasProgressbar($client);

        $node = $client->getCrawler()->filter('div.card#customer_details_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#project_list_box');
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
        $node = $client->getCrawler()->filter('div.card#customer_rates_box');
        self::assertEquals(1, $node->count());
    }

    public function testAddRateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/rate');
        $form = $client->getCrawler()->filter('form[name=customer_rate_form]')->form();
        $client->submit($form, [
            'customer_rate_form' => [
                'rate' => 123.45,
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#customer_rates_box');
        self::assertEquals(1, $node->count());
        $node = $client->getCrawler()->filter('div.card#customer_rates_box table.dataTable tbody tr:not(.summary)');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString('123.45', $node->text(null, true));
    }

    public function testAddCommentAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->assertAccessIsGranted($client, '/admin/customer/1/details');
        $form = $client->getCrawler()->filter('form[name=customer_comment_form]')->form();
        $client->submit($form, [
            'customer_comment_form' => [
                'message' => 'A beautiful and short comment **with some** markdown formatting',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body');
        self::assertStringContainsString('A beautiful and short comment **with some** markdown formatting', $node->html());

        $this->setSystemConfiguration('timesheet.markdown_content', true);

        $this->assertAccessIsGranted($client, '/admin/customer/1/details');
        $node = $client->getCrawler()->filter('div.card#comments_box .direct-chat-text');
        self::assertStringContainsString('<p>A beautiful and short comment <strong>with some</strong> markdown formatting</p>', $node->html());
    }

    public function testDeleteCommentAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/details');
        $form = $client->getCrawler()->filter('form[name=customer_comment_form]')->form();
        $client->submit($form, [
            'customer_comment_form' => [
                'message' => 'Blah foo bar',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();

        $node = $client->getCrawler()->filter('div.card#comments_box .card-body');
        self::assertStringContainsString('Blah foo bar', $node->html());
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body a.delete-comment-link');

        $this->request($client, $node->attr('href'));
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body');
        self::assertStringContainsString('There were no comments posted yet', $node->html());
    }

    public function testDeleteCommentActionWithoutToken(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/details');
        $form = $client->getCrawler()->filter('form[name=customer_comment_form]')->form();
        $client->submit($form, [
            'customer_comment_form' => [
                'message' => 'Blah foo bar',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();

        $comments = $this->getEntityManager()->getRepository(CustomerComment::class)->findAll();
        $id = $comments[0]->getId();

        $this->request($client, '/admin/customer/' . $id . '/comment_delete');

        $this->assertRouteNotFound($client);
    }

    public function testPinCommentAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/details');
        $form = $client->getCrawler()->filter('form[name=customer_comment_form]')->form();
        $client->submit($form, [
            'customer_comment_form' => [
                'message' => 'Blah foo bar',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body');
        self::assertStringContainsString('Blah foo bar', $node->html());
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body a.pin-comment-link.active');
        self::assertEquals(0, $node->count());
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body a.pin-comment-link');
        self::assertEquals(1, $node->count());
        $this->request($client, $node->attr('href'));
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#comments_box .card-body a.pin-comment-link.active');
        self::assertEquals(1, $node->count());
        self::assertStringContainsString('/admin/customer/', $node->attr('href'));
        self::assertStringContainsString('/comment_pin/', $node->attr('href'));
    }

    public function testCreateDefaultTeamAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/details');
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-body');
        self::assertStringContainsString('Visible to everyone, as no team was assigned yet.', $node->text(null, true));

        $this->request($client, '/admin/customer/1/create_team');
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-title');
        self::assertStringContainsString('Only visible to the following teams and all admins.', $node->text(null, true));
        $node = $client->getCrawler()->filter('div.card#team_listing_box .card-body table tbody tr');
        self::assertEquals(1, $node->count());
    }

    public function testProjectsAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/projects/1');
        $node = $client->getCrawler()->filter('div.card#project_list_box .card-body table tbody tr');
        self::assertEquals(1, $node->count());

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $customer = $em->getRepository(Customer::class)->find(1);

        $fixture = new ProjectFixtures();
        $fixture->setAmount(9); // to trigger a second page (every third activity is hidden)
        $fixture->setCustomers([$customer]);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/customer/1/projects/1');

        $node = $client->getCrawler()->filter('div.card#project_list_box .card-footer ul.pagination li');
        self::assertEquals(4, $node->count());

        $node = $client->getCrawler()->filter('div.card#project_list_box .card-body table tbody tr');
        self::assertEquals(5, $node->count());
    }

    public function testCreateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/create');
        $form = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();

        $editForm = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();
        $this->assertEquals(date_default_timezone_get(), $editForm->get('customer_edit_form[timezone]')->getValue());

        $client->submit($form, [
            'customer_edit_form' => [
                'name' => 'Test Customer',
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
        self::getContainer()->get('event_dispatcher')->addSubscriber(new CustomerTestMetaFieldSubscriberMock());
        $this->assertAccessIsGranted($client, '/admin/customer/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();
        $this->assertTrue($form->has('customer_edit_form[metaFields][metatestmock][value]'));
        $this->assertTrue($form->has('customer_edit_form[metaFields][foobar][value]'));
        $this->assertFalse($form->has('customer_edit_form[metaFields][0][value]'));
    }

    public function testEditAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/1/edit');
        $form = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();
        $this->assertEquals('Test', $form->get('customer_edit_form[name]')->getValue());
        $client->submit($form, [
            'customer_edit_form' => [
                'name' => 'Test Customer 2'
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));
        $client->followRedirect();
        $this->request($client, '/admin/customer/1/edit');
        $editForm = $client->getCrawler()->filter('form[name=customer_edit_form]')->form();
        $this->assertEquals('Test Customer 2', $editForm->get('customer_edit_form[name]')->getValue());
    }

    public function testTeamPermissionAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $em = $this->getEntityManager();

        /** @var Customer $customer */
        $customer = $em->getRepository(Customer::class)->find(1);
        self::assertEquals(0, $customer->getTeams()->count());

        $fixture = new TeamFixtures();
        $fixture->setAmount(2);
        $fixture->setAddCustomer(false);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/admin/customer/1/permissions');
        $form = $client->getCrawler()->filter('form[name=customer_team_permission_form]')->form();
        /** @var ChoiceFormField $team1 */
        $team1 = $form->get('customer_team_permission_form[teams][0]');
        $team1->tick();
        /** @var ChoiceFormField $team2 */
        $team2 = $form->get('customer_team_permission_form[teams][1]');
        $team2->tick();

        $client->submit($form);
        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/1/details'));

        /** @var Customer $customer */
        $customer = $em->getRepository(Customer::class)->find(1);
        self::assertEquals(2, $customer->getTeams()->count());
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new CustomerFixtures();
        $fixture->setAmount(1);
        $customers = $this->importFixture($fixture);
        $customer = $customers[0];
        $id = $customer->getId();

        $this->request($client, '/admin/customer/' . $id . '/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->request($client, '/admin/customer/' . $id . '/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/customer/' . $id . '/delete'), $form->getUri());
        $client->submit($form);

        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/customer/' . $id . '/edit');
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

        $this->request($client, '/admin/customer/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/customer/1/delete'), $form->getUri());
        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/'));
        $client->followRedirect();
        $this->assertHasFlashDeleteSuccess($client);
        $this->assertHasNoEntriesWithFilter($client);

        $em->clear();
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(0, \count($timesheets));

        $this->request($client, '/admin/customer/1/edit');
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
        $fixture = new CustomerFixtures();
        $fixture->setAmount(1)->setIsVisible(true);
        $customers = $this->importFixture($fixture);
        $customer = $customers[0];
        $id = $customer->getId();

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(10, \count($timesheets));

        /** @var Timesheet $entry */
        foreach ($timesheets as $entry) {
            $this->assertEquals(1, $entry->getProject()->getCustomer()->getId());
        }

        $this->request($client, '/admin/customer/1/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/customer/1/delete'), $form->getUri());
        $client->submit($form, [
            'form' => [
                'customer' => $id
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/customer/'));
        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(10, \count($timesheets));

        /** @var Timesheet $entry */
        foreach ($timesheets as $entry) {
            $this->assertEquals($id, $entry->getProject()->getCustomer()->getId());
        }

        $this->request($client, '/admin/customer/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testValidationForCreateAction(array $formData, array $validationFields): void
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
                        'country' => '00',
                        'currency' => '00',
                        'timezone' => 'XXX'
                    ]
                ],
                [
                    '#customer_edit_form_name',
                    '#customer_edit_form_country',
                    '#customer_edit_form_currency',
                    '#customer_edit_form_timezone',
                ]
            ],
        ];
    }
}
