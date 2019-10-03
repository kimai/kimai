<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

/**
 * This test makes sure the login and registration work as expected.
 * The logic is located in the FOSUserBundle and already tested, but we use a different layout.
 *
 * @group integration
 */
class SecurityControllerTest extends ControllerBaseTest
{
    public function testRootUrlIsRedirectedToLogin()
    {
        $client = self::createClient();
        $client->request('GET', '/');

        $this->assertIsRedirect($client, $this->createUrl('/homepage'));
        $client->followRedirect();
        $this->assertIsRedirect($client, $this->createUrl('/login'));
    }

    public function testLoginPageIsRendered()
    {
        $client = self::createClient();
        $this->request($client, '/login');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertStringContainsString('<title>Kimai – Time Tracking</title>', $content);
        $this->assertStringContainsString('<form action="/en/login_check" method="post">', $content);
        $this->assertStringContainsString('<input type="text" name="_username"', $content);
        $this->assertStringContainsString('<input name="_password" type="password"', $content);
        $this->assertStringContainsString('<input id="remember_me" name="_remember_me" type="checkbox"', $content);
        $this->assertStringContainsString('">Login</button>', $content);
        $this->assertStringContainsString('<input type="hidden" name="_csrf_token" value="', $content);
        $this->assertStringContainsString('<a href="/en/register/"', $content);
        $this->assertStringContainsString('Register a new account', $content);
    }

    public function testRegisterAccountPageIsRendered()
    {
        $client = self::createClient();
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
        $this->assertStringContainsString('id="fos_user_registration_form_username" name="fos_user_registration_form[username]" required="required" maxlength="60" pattern=".{3,}"', $content);
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
        $this->request($client, '/register/');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=fos_user_registration_form]')->form();
        $client->submit($form, [
            'fos_user_registration_form' => [
                'email' => 'test@example.com',
                'username' => 'example',
                'plainPassword' => [
                    'first' => 'test1234',
                    'second' => 'test1234',
                ],
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/register/confirmed'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('<title>Kimai – Time Tracking</title>', $content);
        $this->assertStringContainsString('<p>Congrats example, your account is now activated.</p>', $content);
        $this->assertStringContainsString('<a href="/en/homepage">', $content);
    }
}
