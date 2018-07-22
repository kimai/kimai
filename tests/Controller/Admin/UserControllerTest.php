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
 * @coversDefaultClass \App\Controller\Admin\UserController
 * @group integration
 * @group legacy
 */
class UserControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/user/');
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/user/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/user/');
        $this->assertHasDataTable($client);
    }

    public function testCreateAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/user/create');
        $form = $client->getCrawler()->filter('form[name=user_create]')->form();
        $this->assertTrue($form->has('user_create[create_more]'));
        $this->assertNull($form->get('user_create[create_more]')->getValue());
        $client->submit($form, [
            'user_create' => [
                'username' => 'foobar@example.com',
                'plainPassword' => ['first' => 'abcdef', 'second' => 'abcdef'],
                'email' => 'foobar@example.com',
                'enabled' => 1,
            ]
        ]);
        $this->assertTrue($client->getResponse()->isRedirect($this->createUrl('/profile/foobar@example.com/edit')));
        $client->followRedirect();
        // TODO test that this is the users profile
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
        $this->assertEquals(1, $form->get('user_create[create_more]')->getValue());
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
                        'username' => 'Test',
                        'plainPassword' => ['first' => 'sdfsdf', 'second' => 'sdfxxx'],
                        'alias' => 'ycvyxcb',
                        'title' => '34rtwrtewrt',
                        'avatar' => 'asdfawer',
                        'email' => 'ydfbvsdfgs',
                        'enabled' => '3',
                    ]
                ],
                [
                    '#user_create_username',
                    '#user_create_plainPassword_first',
                    '#user_create_email',
                    '#user_create_enabled',
                ]
            ],
        ];
    }
}
