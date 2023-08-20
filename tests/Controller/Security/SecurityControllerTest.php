<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Security;

use App\Configuration\SamlConfiguration;
use App\Configuration\SystemConfiguration;
use App\Controller\Security\SecurityController;
use App\Entity\User;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\Controller\ControllerBaseTest;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * This test makes sure the login and registration work as expected.
 * The logic is located in the FOSUserBundle and already tested, but we use a different layout.
 *
 * @group integration
 */
class SecurityControllerTest extends ControllerBaseTest
{
    public function testRootUrlIsRedirectedToLogin(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        $this->assertIsRedirect($client, $this->createUrl('/homepage'));
        $client->followRedirect();
        $this->assertIsRedirect($client, $this->createUrl('/login'));
    }

    public function testLoginPageIsRendered(): void
    {
        $client = self::createClient();
        $this->request($client, '/login');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertStringContainsString('<title>Kimai – Time Tracking</title>', $content);
        $this->assertStringContainsString('<form action="/en/login_check" method="post"', $content);
        $this->assertStringContainsString('<input type="text" id="username" name="_username"', $content);
        $this->assertStringContainsString('<input id="password" name="_password" type="password"', $content);
        $this->assertStringContainsString('">Log in</button>', $content);
        $this->assertStringContainsString('<input type="hidden" name="_csrf_token" value="', $content);
        $this->assertStringNotContainsString('<a href="/en/register/"', $content);
        $this->assertStringNotContainsString('Register a new account', $content);
    }

    public function testLoginPositive(): void
    {
        $client = self::createClient();
        $this->request($client, '/login');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('body form')->form();
        $client->submit($form, [
            '_username' => 'susan_super',
            '_password' => 'kitten'
        ]);

        $this->assertIsRedirect($client); // redirect to root URL
        $client->followRedirect();

        $this->assertIsRedirect($client, '/homepage'); // redirect to homepage
        $client->followRedirect();

        $this->assertIsRedirect($client, '/timesheet/'); // redirect to configured start page
        $client->followRedirect();

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testLoginAlreadyLoggedIn(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->request($client, '/login');

        $this->assertIsRedirect($client, '/homepage'); // redirect to homepage
        $client->followRedirect();

        $this->assertIsRedirect($client, '/timesheet/'); // redirect to configured start page
        $client->followRedirect();

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testLoginNegative(): void
    {
        $client = self::createClient();
        $this->request($client, '/login');

        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('body form')->form();
        $client->submit($form, [
            '_username' => 'susan_super',
            '_password' => '1234567890'
        ]);

        $this->assertIsRedirect($client); // redirect to root URL
        $client->followRedirect();

        $this->assertTrue($client->getResponse()->isSuccessful());
        self::assertStringContainsString('<div class="alert alert-important alert-danger">Invalid credentials.</div>', $client->getResponse()->getContent());
    }

    public function testCheckAction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');

        self::createClient(); // just to bootstrap the container
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $systemConfig = new SystemConfiguration(new TestConfigLoader([]), ['saml' => ['activate' => true]]);
        $samlConfig = new SamlConfiguration($systemConfig);
        $sut = new SecurityController($csrf, $samlConfig);
        $sut->checkAction();
    }

    public function testLogoutAction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must activate the logout in your security firewall configuration.');

        self::createClient(); // just to bootstrap the container
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $systemConfig = new SystemConfiguration(new TestConfigLoader([]), ['saml' => ['activate' => true]]);
        $samlConfig = new SamlConfiguration($systemConfig);
        $sut = new SecurityController($csrf, $samlConfig);
        $sut->logoutAction();
    }
}
