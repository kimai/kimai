<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Security;

use App\Tests\Controller\ControllerBaseTest;

/**
 * @group integration
 */
class SelfRegistrationControllerTest extends ControllerBaseTest
{
    public function testRegisterAccountPageIsRendered()
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', true);
        $this->request($client, '/register/');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $content = $response->getContent();
        $this->assertStringContainsString('<title>Kimai – Time Tracking</title>', $content);
        $this->assertStringContainsString('Register a new account', $content);
        $this->assertStringContainsString('<form name="fos_user_registration_form" method="post" action="/en/register/" class="fos_user_registration_register">', $content);
        $this->assertStringContainsString('<input type="email"', $content);
        $this->assertStringContainsString('id="fos_user_registration_form_email" name="fos_user_registration_form[email]" required="required"', $content);
        $this->assertStringContainsString('<input type="text"', $content);
        $this->assertStringContainsString('id="fos_user_registration_form_username" name="fos_user_registration_form[username]" required="required" maxlength="60" pattern=".{2,}"', $content);
        $this->assertStringContainsString('<input type="password"', $content);
        $this->assertStringContainsString('id="fos_user_registration_form_plainPassword_first" name="fos_user_registration_form[plainPassword][first]" required="required"', $content);
        $this->assertStringContainsString('id="fos_user_registration_form_plainPassword_second" name="fos_user_registration_form[plainPassword][second]" required="required"', $content);
        $this->assertStringContainsString('<input type="hidden"', $content);
        $this->assertStringContainsString('id="fos_user_registration_form__token" name="fos_user_registration_form[_token]"', $content);
        $this->assertStringContainsString('>Register</button>', $content);
    }

    public function testRegisterAccount()
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', true);
        $this->request($client, '/register/');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=fos_user_registration_form]')->form();
        $client->submit($form, [
            'fos_user_registration_form' => [
                'email' => 'register@example.com',
                'username' => 'example',
                'plainPassword' => [
                    'first' => 'test1234',
                    'second' => 'test1234',
                ],
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/register/check-email'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('<title>Kimai – Time Tracking</title>', $content);
        $this->assertStringContainsString('An email has been sent to register@example.com. It contains an activation link you must click to activate your account.', $content);
        $this->assertStringContainsString('<a href="/en/login">', $content);
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testRegisterActionWithValidationProblems(array $formData, array $validationFields)
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', true);

        $this->assertHasValidationError($client, '/register/', 'form[name=fos_user_registration_form]', $formData, $validationFields);
    }

    public function getValidationTestData()
    {
        return [
            [
                // invalid fields: username, password_second, email
                [
                    'fos_user_registration_form' => [
                        'username' => '',
                        'plainPassword' => ['first' => 'sdfsdf123'],
                        'email' => '',
                    ]
                ],
                [
                    '#fos_user_registration_form_username',
                    '#fos_user_registration_form_plainPassword_first',
                    '#fos_user_registration_form_email',
                ]
            ],
            // invalid fields: username, password, email
            [
                [
                    'fos_user_registration_form' => [
                        'username' => 'x',
                        'plainPassword' => ['first' => 'sdfsdf123', 'second' => 'sdfxxxxxxx'],
                        'email' => 'ydfbvsdfgs',
                    ]
                ],
                [
                    '#fos_user_registration_form_username',
                    '#fos_user_registration_form_plainPassword_first',
                    '#fos_user_registration_form_email',
                ]
            ],
            // invalid fields: password (too short)
            [
                [
                    'fos_user_registration_form' => [
                        'username' => 'test123',
                        'plainPassword' => ['first' => 'test123', 'second' => 'test123'],
                        'email' => 'ydfbvsdfgs@example.com',
                    ]
                ],
                [
                    '#fos_user_registration_form_plainPassword_first',
                ]
            ],
        ];
    }
}
