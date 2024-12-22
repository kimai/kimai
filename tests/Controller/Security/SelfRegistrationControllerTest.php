<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Security;

use App\Entity\User;
use App\Tests\Controller\AbstractControllerBaseTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @group integration
 */
class SelfRegistrationControllerTest extends AbstractControllerBaseTestCase
{
    private function assertRegisterActionWithDeactivatedFeature(string $route): void
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', false);
        $this->request($client, $route);
        $this->assertRouteNotFound($client);
    }

    public function testRegisterWithDeactivatedFeature(): void
    {
        $this->assertRegisterActionWithDeactivatedFeature('/register/');
    }

    public function testCheckEmailWithDeactivatedFeature(): void
    {
        $this->assertRegisterActionWithDeactivatedFeature('/register/check-email');
    }

    public function testConfirmWithDeactivatedFeature(): void
    {
        $this->assertRegisterActionWithDeactivatedFeature('/register/confirm/123123');
    }

    public function testConfirmedWithDeactivatedFeature(): void
    {
        $this->assertRegisterActionWithDeactivatedFeature('/register/confirmed');
    }

    public function testRegisterAccountPageIsRendered(): void
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', true);
        $this->request($client, '/register/');

        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());

        $content = $response->getContent();
        self::assertStringContainsString('<title>Kimai – Time Tracking</title>', $content);
        self::assertStringContainsString('Register a new account', $content);
        self::assertStringContainsString('<form name="user_registration_form" method="post" action="/en/register/"', $content);
        self::assertStringContainsString('<input type="email"', $content);
        self::assertStringContainsString('id="user_registration_form_email" name="user_registration_form[email]" required="required"', $content);
        self::assertStringContainsString('<input type="text"', $content);
        self::assertStringContainsString('id="user_registration_form_username" name="user_registration_form[username]" required="required" maxlength="64" pattern="', $content);
        self::assertStringContainsString('<input type="password"', $content);
        self::assertStringContainsString('id="user_registration_form_plainPassword_first" name="user_registration_form[plainPassword][first]" required="required"', $content);
        self::assertStringContainsString('id="user_registration_form_plainPassword_second" name="user_registration_form[plainPassword][second]" required="required"', $content);
        self::assertStringContainsString('<input type="hidden"', $content);
        self::assertStringContainsString('id="user_registration_form__token" name="user_registration_form[_token]"', $content);
        self::assertStringContainsString('>Register</button>', $content);
    }

    private function createUser(KernelBrowser $client, string $username, string $email, string $password): User
    {
        $this->setSystemConfiguration('user.registration', true);
        $this->request($client, '/register/');

        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=user_registration_form]')->form();
        $client->submit($form, [
            'user_registration_form' => [
                'email' => $email,
                'username' => $username,
                'plainPassword' => [
                    'first' => $password,
                    'second' => $password,
                ],
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/register/check-email'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        return $this->loadUserFromDatabase($username);
    }

    public function testCheckEmailWithoutEmail(): void
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', true);
        $this->request($client, '/register/check-email');

        $this->assertIsRedirect($client, $this->createUrl('/register/'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testRegisterAccount(): void
    {
        $client = self::createClient();
        $this->createUser($client, 'example', 'register@example.com', 'test1234');

        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('<title>Kimai – Time Tracking</title>', $content);
        self::assertStringContainsString('An e-mail has been sent to register@example.com. It contains a link you must click to activate your account.', $content);
        self::assertStringContainsString('<a href="/en/login">', $content);
    }

    public function testConfirmWithInvalidToken(): void
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', true);
        $this->request($client, '/register/confirm/1234567890');

        $this->assertIsRedirect($client, $this->createUrl('/login'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testConfirmAccount(): void
    {
        $client = self::createClient();
        $user = $this->createUser($client, 'example', 'register@example.com', 'test1234');

        $token = $user->getConfirmationToken();
        self::assertNotEmpty($token);
        self::assertFalse($user->isEnabled());

        $this->request($client, '/register/confirm/' . $token);
        $this->assertIsRedirect($client, $this->createUrl('/register/confirmed'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('Congratulations example, your account is now activated.', $content);

        $user = $this->loadUserFromDatabase('example');
        self::assertTrue($user->isEnabled());
    }

    public function testConfirmedAnonymousRedirectsToLogin(): void
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', true);
        $this->request($client, '/register/confirmed');

        // AccessDeniedException redirects to login
        $this->assertIsRedirect($client, $this->createUrl('/login'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider getValidationTestData
     */
    public function testRegisterActionWithValidationProblems(array $formData, array $validationFields): void
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.registration', true);

        $this->assertHasValidationError($client, '/register/', 'form[name=user_registration_form]', $formData, $validationFields);
    }

    public static function getValidationTestData(): array // @phpstan-ignore missingType.iterableValue
    {
        return [
            [
                // invalid fields: username, password_second, email
                [
                    'user_registration_form' => [
                        'username' => '',
                        'plainPassword' => ['first' => 'sdfsdf123'],
                        'email' => '',
                    ]
                ],
                [
                    '#user_registration_form_username',
                    '#user_registration_form_plainPassword_first',
                    '#user_registration_form_email',
                ]
            ],
            // invalid fields: username, password, email
            [
                [
                    'user_registration_form' => [
                        'username' => 'x',
                        'plainPassword' => ['first' => 'sdfsdf123', 'second' => 'sdfxxxxxxx'],
                        'email' => 'ydfbvsdfgs',
                    ]
                ],
                [
                    '#user_registration_form_username',
                    '#user_registration_form_plainPassword_first',
                    '#user_registration_form_email',
                ]
            ],
            // invalid fields: password (too short)
            [
                [
                    'user_registration_form' => [
                        'username' => 'test123',
                        'plainPassword' => ['first' => 'test123', 'second' => 'test123'],
                        'email' => 'ydfbvsdfgs@example.com',
                    ]
                ],
                [
                    '#user_registration_form_plainPassword_first',
                ]
            ],
        ];
    }
}
