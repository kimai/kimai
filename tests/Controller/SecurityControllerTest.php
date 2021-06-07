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
        $this->assertStringNotContainsString('<a href="/en/register/"', $content);
        $this->assertStringNotContainsString('Register a new account', $content);
    }
}
