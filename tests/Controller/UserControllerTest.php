<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @group integration
 */
class UserControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/admin/user/');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/user/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/user/');
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_user_admin', 7);
        $this->assertPageActions($client, [
            'download toolbar-action' => $this->createUrl('/admin/user/export'),
            'create modal-ajax-form' => $this->createUrl('/admin/user/create'),
            'dropdown-item action-weekly' => $this->createUrl('/reporting/users/week'),
            'dropdown-item action-monthly' => $this->createUrl('/reporting/users/month'),
            'dropdown-item action-yearly' => $this->createUrl('/reporting/users/year'),
        ]);
    }

    public function testIndexActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/admin/user/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'searchTerm' => 'hourly_rate:35 tony',
            'role' => 'ROLE_TEAMLEAD',
            'visibility' => 1,
            'size' => 50,
            'page' => 1,
        ]);

        self::assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_user_admin', 1);
    }

    public function testExportIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/user/export');
    }

    public function testExportAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/user/export');
        $this->assertExcelExportResponse($client, 'kimai-users_');
    }

    public function testExportActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/admin/user/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/admin/user/export'));
        $client->submit($form, [
            'searchTerm' => 'hourly_rate:35 tony',
            'role' => 'ROLE_TEAMLEAD',
            'visibility' => 1,
            'size' => 50,
            'page' => 1,
        ]);

        $this->assertExcelExportResponse($client, 'kimai-users_');
    }

    public function testCreateAction(): void
    {
        $username = '亚历山德拉' . uniqid();
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/user/create');
        $form = $client->getCrawler()->filter('form[name=user_create]')->form();
        $client->submit($form, [
            'user_create' => [
                'username' => $username,
                'alias' => $username,
                'plainPassword' => ['first' => '12345678', 'second' => '12345678'],
                'email' => 'foobar@example.com',
                'enabled' => 1,
            ]
        ]);

        $location = $this->assertIsModalRedirect($client, '/profile/' . urlencode($username) . '/edit');
        $this->requestPure($client, $location);

        $form = $client->getCrawler()->filter('form[name=user_edit]')->form();
        self::assertEquals($username, $form->get('user_edit[alias]')->getValue());
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/admin/user/4/delete');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        self::assertStringEndsWith($this->createUrl('/admin/user/4/delete'), $form->getUri());
        $client->submit($form);

        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/user/4/edit');
        self::assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntries(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture->setUser($user);
        $fixture->setAmount(10);
        $this->importFixture($fixture);

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertEquals(10, \count($timesheets));

        $this->request($client, '/admin/user/' . $user->getId() . '/delete');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        self::assertStringEndsWith($this->createUrl('/admin/user/' . $user->getId() . '/delete'), $form->getUri());
        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/admin/user/'));
        $client->followRedirect();
        $this->assertHasFlashDeleteSuccess($client);

        $em->clear();
        $timesheets = $em->getRepository(Timesheet::class)->count([]);
        self::assertEquals(0, $timesheets);

        $this->request($client, '/admin/user/' . $user->getId() . '/edit');
        self::assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithUserReplacementAndTimesheetEntries(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);
        $userNew = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $this->assertNotEquals($userNew->getId(), $user->getId());

        $fixture = new TimesheetFixtures();
        $fixture->setUser($user);
        $fixture->setAmount(10);
        $this->importFixture($fixture);

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertEquals(10, \count($timesheets));
        foreach ($timesheets as $timesheet) {
            self::assertEquals($user->getId(), $timesheet->getUser()->getId());
        }

        $this->request($client, '/admin/user/' . $user->getId() . '/delete');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        self::assertStringEndsWith($this->createUrl('/admin/user/' . $user->getId() . '/delete'), $form->getUri());
        $client->submit($form, [
            'form' => [
                'user' => $userNew->getId()
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/admin/user/'));
        $client->followRedirect();
        $this->assertHasFlashDeleteSuccess($client);

        $em->clear();
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertEquals(10, \count($timesheets));
        foreach ($timesheets as $timesheet) {
            self::assertEquals($userNew->getId(), $timesheet->getUser()->getId());
        }

        $this->request($client, '/admin/user/' . $user->getId() . '/edit');
        self::assertFalse($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testValidationForCreateAction(array $formData, array $validationFields): void
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/user/create',
            'form[name=user_create]',
            $formData,
            $validationFields
        );
    }

    public static function getValidationTestData()
    {
        return [
            [
                // invalid fields: username, password_second, email, enabled
                [
                    'user_create' => [
                        'username' => '',
                        'plainPassword' => ['first' => 'sdfsdf123'],
                        'alias' => 'ycvyxcb',
                        'title' => '34rtwrtewrt',
                        'email' => '',
                    ]
                ],
                [
                    '#user_create_username',
                    '#user_create_plainPassword_first',
                    '#user_create_email',
                ]
            ],
            // invalid fields: username, password, email, enabled
            [
                [
                    'user_create' => [
                        'username' => 'x',
                        'plainPassword' => ['first' => 'sdfsdf123', 'second' => 'sdfxxxxxxx'],
                        'alias' => 'ycvyxcb',
                        'title' => '34rtwrtewrt',
                        'email' => 'ydfbvsdfgs',
                    ]
                ],
                [
                    '#user_create_username',
                    '#user_create_plainPassword_first',
                    '#user_create_email',
                ]
            ],
            // invalid fields: password (too short)
            [
                [
                    'user_create' => [
                        'username' => 'test123',
                        'plainPassword' => ['first' => 'test123', 'second' => 'test123'],
                        'alias' => 'ycvyxcb',
                        'title' => '34rtwrtewrt',
                        'email' => 'ydfbvsdfgs@example.com',
                    ]
                ],
                [
                    '#user_create_plainPassword_first',
                ]
            ],
        ];
    }
}
