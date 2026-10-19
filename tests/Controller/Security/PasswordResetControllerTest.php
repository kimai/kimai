<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Security;

use App\Tests\Controller\AbstractControllerBaseTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class PasswordResetControllerTest extends AbstractControllerBaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setTrustedHosts('localhost');
    }

    protected function tearDown(): void
    {
        unset($_ENV['TRUSTED_HOSTS'], $_SERVER['TRUSTED_HOSTS']);
        // the kernel sets the trusted hosts process wide, reset them so they do not leak into other tests
        Request::setTrustedHosts([]);
        parent::tearDown();
    }

    private function setTrustedHosts(string $trustedHosts): void
    {
        $_ENV['TRUSTED_HOSTS'] = $trustedHosts;
        $_SERVER['TRUSTED_HOSTS'] = $trustedHosts;
    }

    private function testResetActionWithoutTrustedHosts(string $route, string $method = 'GET'): void
    {
        $this->setTrustedHosts('');
        $client = self::createClient();
        $this->setSystemConfiguration('user.password_reset', true);
        $this->request($client, $route, $method);
        self::assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $client->getResponse()->getStatusCode());
    }

    public function testResetRequestWithoutTrustedHosts(): void
    {
        $this->testResetActionWithoutTrustedHosts('/resetting/request');
    }

    public function testSendEmailRequestWithoutTrustedHosts(): void
    {
        $this->testResetActionWithoutTrustedHosts('/resetting/send-email', 'POST');
    }

    public function testCheckEmailWithoutTrustedHosts(): void
    {
        $this->testResetActionWithoutTrustedHosts('/resetting/check-email');
    }

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
        self::assertTrue($response->isSuccessful());

        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('<title>Kimai</title>', $content);
        self::assertStringContainsString('Reset your password', $content);
        self::assertStringContainsString('<form class="card-body security-password-reset" action="/en/resetting/send-email" method="post" autocomplete="off">', $content);
        self::assertStringContainsString('<input autocomplete="username" type="text"', $content);
        self::assertStringContainsString('id="username" name="username" required="required"', $content);
        self::assertStringContainsString('Reset your password', $content);

        $form = $client->getCrawler()->filter('form')->form();
        $client->submit($form, [
            'username' => 'john_user',
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        // requesting a password reset does not require authentication: it must not change the account,
        // the password reset is required after the login link was used
        $user = $this->loadUserFromDatabase('john_user');
        self::assertFalse($user->requiresPasswordReset());
        self::assertTrue($user->isPasswordRequestNonExpired(3600));
        self::assertEquals('', $user->getSignatureDate());
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('An email has been sent with a link to reset your password.', $content);
        self::assertStringContainsString('Note: You can only request a new password once every 1:00 hours.', $content);
    }

    public function testResetWithEmptyUsername(): void
    {
        $client = self::createClient();
        $this->request($client, '/resetting/send-email', 'POST', ['username' => '']);
        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('An email has been sent with a link to reset your password.', $content);
        self::assertStringContainsString('Note: You can only request a new password once every 1:00 hours.', $content);
    }

    public function testResetWithUnknownUsername(): void
    {
        $client = self::createClient();
        $this->request($client, '/resetting/send-email', 'POST', ['username' => 'foobar']);
        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('An email has been sent with a link to reset your password.', $content);
        self::assertStringContainsString('Note: You can only request a new password once every 1:00 hours.', $content);
    }

    public function testResetWithKnownUsername(): void
    {
        $client = self::createClient();

        $user = $this->loadUserFromDatabase('john_user');
        self::assertFalse($user->requiresPasswordReset());

        $this->request($client, '/resetting/request');
        self::assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form')->form();
        $client->submit($form, [
            'username' => 'john_user',
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('An email has been sent with a link to reset your password.', $content);
        self::assertStringContainsString('Note: You can only request a new password once every 1:00 hours.', $content);

        // requesting a password reset does not require authentication: it must not change the account,
        // the password reset is required after the login link was used
        $user = $this->loadUserFromDatabase('john_user');
        self::assertFalse($user->requiresPasswordReset());
        self::assertTrue($user->isPasswordRequestNonExpired(3600));
        self::assertEquals('', $user->getSignatureDate());
    }

    /**
     * Regression test: requiring the password reset changes the security signature, which is part of the
     * login link hash. The link must be created afterwards, otherwise the emailed link is invalid immediately.
     */
    public function testLoginLinkFromEmailIsValid(): void
    {
        $client = self::createClient();

        $this->request($client, '/resetting/request');
        self::assertTrue($client->getResponse()->isSuccessful());
        $form = $client->getCrawler()->filter('form')->form();
        $client->submit($form, [
            'username' => 'john_user',
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(TemplatedEmail::class, $email);
        // the context is removed after rendering the email
        preg_match('#href="([^"]+/auth/link/check\?[^"]+)"#', (string) $email->getHtmlBody(), $matches);
        self::assertArrayHasKey(1, $matches, 'Missing login link in email');
        $loginLink = html_entity_decode($matches[1]);

        $user = $this->loadUserFromDatabase('john_user');
        self::assertFalse($user->requiresPasswordReset());
        self::assertEquals('', $user->getSignatureDate());

        $this->requestPure($client, $loginLink);
        $this->assertIsRedirect($client);
        self::assertStringNotContainsString('/login', (string) $client->getResponse()->headers->get('Location'), 'The login link is invalid');

        // using the login link requires the password reset, which changes the security signature
        $this->getEntityManager()->clear();
        $user = $this->loadUserFromDatabase('john_user');
        self::assertTrue($user->requiresPasswordReset());
        self::assertNotEquals('', $user->getSignatureDate());

        $client->followRedirect();
        $this->assertIsRedirect($client, '/wizard/password');
        $this->assertAccessIsGranted($client, '/wizard/password');

        // the changed security signature invalidates the login link
        $client->getCookieJar()->clear();
        $this->requestPure($client, $loginLink);
        $this->assertIsRedirect($client, '/login');
    }

    public function testPartiallyMatchingHostIsRejected(): void
    {
        // the login link is generated from the request host: TRUSTED_HOSTS="localhost" may not trust "localhost.attacker.example"
        $client = self::createClient([], ['HTTP_HOST' => 'localhost.attacker.example']);

        $this->request($client, '/resetting/request');
        self::assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());

        $this->request($client, '/resetting/send-email', 'POST', ['username' => 'john_user']);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
    }
}
