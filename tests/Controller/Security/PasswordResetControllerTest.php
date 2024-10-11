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
class PasswordResetControllerTest extends ControllerBaseTest
{
    private function testResetActionWithDeactivatedFeature(string $route, string $method = 'GET'): void
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.password_reset', false);
        $this->request($client, $route, $method);
        $this->assertRouteNotFound($client);
    }

    public function testResetRequestWithDeactivatedFeature(): void
    {
        $this->testResetActionWithDeactivatedFeature('/resetting/request');
    }

    public function testSendEmailRequestWithDeactivatedFeature(): void
    {
        $this->testResetActionWithDeactivatedFeature('/resetting/send-email', 'POST');
    }

    public function testCheckEmailWithDeactivatedFeature(): void
    {
        $this->testResetActionWithDeactivatedFeature('/resetting/check-email');
    }

    public function testResetRequestPageIsRendered(): void
    {
        $client = self::createClient();

        $this->setSystemConfiguration('user.password_reset', true);
        $this->request($client, '/resetting/request');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('<title>Kimai – Time Tracking</title>', $content);
        $this->assertStringContainsString('Reset your password', $content);
        $this->assertStringContainsString('<form class="card-body security-password-reset" action="/en/resetting/send-email" method="post" autocomplete="off">', $content);
        $this->assertStringContainsString('<input autocomplete="username" type="text"', $content);
        $this->assertStringContainsString('id="username" name="username" required="required"', $content);
        $this->assertStringContainsString('Reset your password', $content);

        $form = $client->getCrawler()->filter('form')->form();
        $client->submit($form, [
            'username' => 'john_user',
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        // TODO test the actual email and provided login link

        $user = $this->loadUserFromDatabase('john_user');
        $this->assertTrue($user->requiresPasswordReset());
    }

    public function testRequestAsLoggedInUserRedirects(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/resetting/request');
        $this->assertIsRedirect($client, $this->createUrl('/homepage'));
    }

    public function testResetAsLoggedInUserRedirects(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/resetting/send-email', 'POST');
        $this->assertIsRedirect($client, $this->createUrl('/homepage'));
    }

    public function testResetWithMissingUsername(): void
    {
        $client = self::createClient();
        $this->request($client, '/resetting/send-email', 'POST');
        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('An email has been sent with a link to reset your password.', $content);
        $this->assertStringContainsString('Note: You can only request a new password once every 1:00 hours.', $content);
    }

    public function testResetWithEmptyUsername(): void
    {
        $client = self::createClient();
        $this->request($client, '/resetting/send-email', 'POST', ['username' => '']);
        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('An email has been sent with a link to reset your password.', $content);
        $this->assertStringContainsString('Note: You can only request a new password once every 1:00 hours.', $content);
    }

    public function testResetWithUnknownUsername(): void
    {
        $client = self::createClient();
        $this->request($client, '/resetting/send-email', 'POST', ['username' => 'foobar']);
        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('An email has been sent with a link to reset your password.', $content);
        $this->assertStringContainsString('Note: You can only request a new password once every 1:00 hours.', $content);
    }

    public function testResetWithKnownUsername(): void
    {
        $client = self::createClient();

        $user = $this->loadUserFromDatabase('john_user');
        $this->assertFalse($user->requiresPasswordReset());

        $this->request($client, '/resetting/request');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form')->form();
        $client->submit($form, [
            'username' => 'john_user',
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('An email has been sent with a link to reset your password.', $content);
        $this->assertStringContainsString('Note: You can only request a new password once every 1:00 hours.', $content);

        // TODO test the actual email and provided login link

        $user = $this->loadUserFromDatabase('john_user');
        $this->assertTrue($user->requiresPasswordReset());
    }
}
