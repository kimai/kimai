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
 * They live in the FOSUserBundle and are tested already, but we use a different layout.
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
        $this->assertContains('<title>Kimai - Time Tracking</title>', $content);
        $this->assertContains('<form action="/en/login_check" method="post">', $content);
        $this->assertContains('<input type="text" name="_username"', $content);
        $this->assertContains('<input name="_password" type="password"', $content);
        $this->assertContains('<input id="remember_me" name="_remember_me" type="checkbox"', $content);
        $this->assertContains('">Login</button>', $content);
        $this->assertContains('<input type="hidden" name="_csrf_token" value="', $content);
        $this->assertContains('<a href="/en/register/"', $content);
        $this->assertContains('Register a new account', $content);
    }

    public function testRegisterAccountPageIsRendered()
    {
        $client = self::createClient();
        $this->request($client, '/register/');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('<title>Kimai - Time Tracking</title>', $content);
        $this->assertContains('Register a new account', $content);
        $this->assertContains('<form name="fos_user_registration_form" method="post" action="/en/register/" class="fos_user_registration_register">', $content);
        $this->assertContains('<input type="email"', $content);
        $this->assertContains('id="fos_user_registration_form_email" name="fos_user_registration_form[email]" required="required"', $content);
        $this->assertContains('<input type="text"', $content);
        $this->assertContains('id="fos_user_registration_form_username" name="fos_user_registration_form[username]" required="required" maxlength="60" pattern=".{3,}"', $content);
        $this->assertContains('<input type="password"', $content);
        $this->assertContains('id="fos_user_registration_form_plainPassword_first" name="fos_user_registration_form[plainPassword][first]" required="required"', $content);
        $this->assertContains('id="fos_user_registration_form_plainPassword_second" name="fos_user_registration_form[plainPassword][second]" required="required"', $content);
        $this->assertContains('<input type="hidden"', $content);
        $this->assertContains('id="fos_user_registration_form__token" name="fos_user_registration_form[_token]"', $content);
        $this->assertContains('>Register</button>', $content);
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
                    'first' => 'test123',
                    'second' => 'test123',
                ],
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/register/confirmed'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        $this->assertContains('<title>Kimai - Time Tracking</title>', $content);
        $this->assertContains('<p>Congrats example, your account is now activated.</p>', $content);
        $this->assertContains('<a href="/en/homepage">', $content);
    }
}
