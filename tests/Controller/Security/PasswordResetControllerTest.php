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
    private function testResetActionWithDeactivatedFeature(string $route, string $method = 'GET')
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

    public function testResetWithDeactivatedFeature(): void
    {
        $this->testResetActionWithDeactivatedFeature('/resetting/reset/1234567890');
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
        $this->assertStringContainsString('<input type="text"', $content);
        $this->assertStringContainsString('id="username" name="username" required="required"', $content);
        $this->assertStringContainsString('Reset your password', $content);

        $form = $client->getCrawler()->filter('form')->form();
        $client->submit($form, [
            'username' => 'john_user',
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $user = $this->loadUserFromDatabase('john_user');
        $token = $user->getConfirmationToken();

        $this->request($client, '/resetting/reset/' . $token);
        $this->assertTrue($client->getResponse()->isSuccessful());
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
        $this->assertStringContainsString('Note: You can only request a new password once every 2:00 hours.', $content);
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
        $this->assertStringContainsString('Note: You can only request a new password once every 2:00 hours.', $content);
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
        $this->assertStringContainsString('Note: You can only request a new password once every 2:00 hours.', $content);
    }

    public function testResetWithKnownUsername(): void
    {
        $client = self::createClient();

        $user = $this->loadUserFromDatabase('john_user');
        $token = $user->getConfirmationToken();
        $this->assertNull($token);

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
        $this->assertStringContainsString('Note: You can only request a new password once every 2:00 hours.', $content);

        $user = $this->loadUserFromDatabase('john_user');
        $token = $user->getConfirmationToken();
        $this->assertNotNull($token);
        $this->assertGreaterThan(10, \strlen($token));
    }
}
