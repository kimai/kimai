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
class UserControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/user/');
    }

    public function testIsSecureForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/user/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/user/');
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_user_admin', 7);
        $this->assertPageActions($client, [
            'search search-toggle visible-xs-inline' => '#',
            'visibility' => '#',
            'permissions' => $this->createUrl('/admin/permissions'),
            'create' => $this->createUrl('/admin/user/create'),
            'help' => 'https://www.kimai.org/documentation/users.html'
        ]);
    }

    public function testIndexActionWithSearchTermQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/admin/user/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form.header-search')->form();
        $client->submit($form, [
            'searchTerm' => 'hourly_rate:35 tony',
            'role' => 'ROLE_TEAMLEAD',
            'visibility' => 1,
            'pageSize' => 50,
            'page' => 1,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_user_admin', 1);
    }

    public function testCreateAction()
    {
        $username = '亚历山德拉';
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/user/create');
        $form = $client->getCrawler()->filter('form[name=user_create]')->form();
        $this->assertTrue($form->has('user_create[create_more]'));
        $this->assertFalse($form->get('user_create[create_more]')->hasValue());
        $client->submit($form, [
            'user_create' => [
                'username' => $username,
                'alias' => $username,
                'plainPassword' => ['first' => 'abcdef', 'second' => 'abcdef'],
                'email' => 'foobar@example.com',
                'enabled' => 1,
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode($username) . '/edit'));
        $client->followRedirect();

        $expectedTabs = ['#settings', '#password', '#api-token', '#teams', '#roles'];

        $tabs = $client->getCrawler()->filter('div.nav-tabs-custom ul.nav-tabs li');
        $this->assertEquals(\count($expectedTabs), $tabs->count());
        $foundTabs = [];
        /** @var \DOMElement $tab */
        foreach ($tabs->filter('a') as $tab) {
            $foundTabs[] = $tab->getAttribute('href');
        }
        $this->assertEmpty(array_diff($expectedTabs, $foundTabs));

        $form = $client->getCrawler()->filter('form[name=user_edit]')->form();
        $this->assertEquals($username, $form->get('user_edit[alias]')->getValue());
    }

    public function testCreateActionWithCreateMore()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/user/create');
        $form = $client->getCrawler()->filter('form[name=user_create]')->form();
        $this->assertTrue($form->has('user_create[create_more]'));
        $client->submit($form, [
            'user_create' => [
                'username' => 'foobar@example.com',
                'plainPassword' => ['first' => 'abcdef', 'second' => 'abcdef'],
                'email' => 'foobar@example.com',
                'enabled' => 1,
                'create_more' => true,
            ]
        ]);
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form[name=user_create]')->form();
        $this->assertTrue($form->has('user_create[create_more]'));
        $this->assertTrue($form->get('user_create[create_more]')->hasValue());
        $this->assertEquals(1, $form->get('user_create[create_more]')->getValue());
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/admin/user/4/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/user/4/delete'), $form->getUri());
        $client->submit($form);

        $client->followRedirect();
        $this->assertHasDataTable($client);
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/admin/user/4/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testDeleteActionWithTimesheetEntries()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture->setUser($user);
        $fixture->setAmount(10);
        $this->importFixture($fixture);

        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertEquals(10, \count($timesheets));

        $this->request($client, '/admin/user/' . $user->getId() . '/delete');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=form]')->form();
        $this->assertStringEndsWith($this->createUrl('/admin/user/' . $user->getId() . '/delete'), $form->getUri());
        $client->submit($form);

        $this->assertIsRedirect($client, $this->createUrl('/admin/user/'));
        $client->followRedirect();
        $this->assertHasFlashDeleteSuccess($client);

        // SQLIte does not necessarly support onCascade delete, so these timesheet will stay after deletion
        // $em->clear();
        // $timesheets = $em->getRepository(Timesheet::class)->count([]);
        // $this->assertEquals(0, $timesheets);

        $this->request($client, '/admin/user/' . $user->getId() . '/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testValidationForCreateAction(array $formData, array $validationFields)
    {
        $this->assertFormHasValidationError(
            User::ROLE_SUPER_ADMIN,
            '/admin/user/create',
            'form[name=user_create]',
            $formData,
            $validationFields
        );
    }

    public function getValidationTestData()
    {
        return [
            [
                // invalid fields: username, password_second, email, enabled
                [
                    'user_create' => [
                        'username' => '',
                        'plainPassword' => ['first' => 'sdfsdf'],
                        'alias' => 'ycvyxcb',
                        'title' => '34rtwrtewrt',
                        'avatar' => 'asdfawer',
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
                        'plainPassword' => ['first' => 'sdfsdf', 'second' => 'sdfxxx'],
                        'alias' => 'ycvyxcb',
                        'title' => '34rtwrtewrt',
                        'avatar' => 'asdfawer',
                        'email' => 'ydfbvsdfgs',
                    ]
                ],
                [
                    '#user_create_username',
                    '#user_create_plainPassword_first',
                    '#user_create_email',
                ]
            ],
        ];
    }
}
