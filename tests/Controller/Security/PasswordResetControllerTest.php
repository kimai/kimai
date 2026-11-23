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
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

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
        self::assertNotEmpty($user->getPreferenceValue(User::PREFERENCE_PASSWORD_RESET_LINK));
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
        self::assertNotEmpty($user->getPreferenceValue(User::PREFERENCE_PASSWORD_RESET_LINK));
    }

    /**
     * Regression test: requiring the password reset changes the security signature, which is part of the
     * login link hash. The link must be created afterwards, otherwise the emailed link is invalid immediately.
     */
    public function testLoginLinkFromEmailIsValid(): void
    {
        $client = self::createClient();
        $loginLink = $this->requestPasswordReset($client);

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

    public function testLoginLinkFromEmailUsesTokenLifetime(): void
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.password_reset_retry_ttl', 7200);
        $this->setSystemConfiguration('user.password_reset_token_ttl', 900);

        $before = time();
        $loginLink = $this->requestPasswordReset($client);
        $after = time();

        parse_str((string) parse_url($loginLink, PHP_URL_QUERY), $query);
        self::assertArrayHasKey('expires', $query);
        self::assertIsString($query['expires']);
        $expires = (int) $query['expires'];
        self::assertGreaterThanOrEqual($before + 900, $expires);
        self::assertLessThanOrEqual($after + 900, $expires);
    }

    private function requestPasswordReset(HttpKernelBrowser $client, string $username = 'john_user'): string
    {
        $this->request($client, '/resetting/request');
        self::assertTrue($client->getResponse()->isSuccessful());
        $client->submit($client->getCrawler()->filter('form')->form(), ['username' => $username]);
        $this->assertIsRedirect($client, $this->createUrl('/resetting/check-email'));

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(TemplatedEmail::class, $email);
        // the context is removed after rendering the email
        preg_match('#href="([^"]+/auth/link/check\?[^"]+)"#', (string) $email->getHtmlBody(), $matches);
        self::assertArrayHasKey(1, $matches, 'Missing login link in email');

        return html_entity_decode($matches[1]);
    }

    /**
     * Creates a login link like the "kimai:user:login-link" command (without --password-reset).
     */
    private function createLoginLink(string $username): string
    {
        $request = Request::create('http://localhost/');
        $stack = self::getContainer()->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);
        $stack->push($request);

        try {
            $handler = self::getContainer()->get(LoginLinkHandlerInterface::class);
            self::assertInstanceOf(LoginLinkHandlerInterface::class, $handler);

            return $handler->createLoginLink($this->loadUserFromDatabase($username), $request)->getUrl();
        } finally {
            $stack->pop();
        }
    }

    /**
     * Regression test: the password reset is bound to the emailed link. Any other login link (e.g. created by an
     * administrator) is a normal login, even while a reset request is pending: it must neither require a password
     * change (which does not ask for the current password) nor change the security signature.
     */
    public function testOtherLoginLinkDoesNotRequirePasswordReset(): void
    {
        $client = self::createClient();
        $this->requestPasswordReset($client);
        $loginLink = $this->createLoginLink('john_user');

        $this->requestPure($client, $loginLink);
        $this->assertIsRedirect($client);
        self::assertStringNotContainsString('/login', (string) $client->getResponse()->headers->get('Location'), 'The login link is invalid');

        $this->getEntityManager()->clear();
        $user = $this->loadUserFromDatabase('john_user');
        self::assertFalse($user->requiresPasswordReset());
        self::assertEquals('', $user->getSignatureDate());

        // the user is logged in, but cannot set a new password without the current one
        $this->request($client, '/wizard/password');
        $this->assertIsRedirect($client, '/homepage');
        $this->assertAccessIsGranted($client, '/timesheet/');
    }

    /**
     * The success handler is configured in security.yaml and must receive the firewall name from Symfony,
     * otherwise the target path saved in the session (the page requested before the login) is ignored.
     */
    public function testLoginLinkRedirectsToSavedTargetPath(): void
    {
        $client = self::createClient();

        // the firewall saves the target path in the session
        $this->request($client, '/timesheet/');
        $this->assertIsRedirect($client, '/login');

        $loginLink = $this->requestPasswordReset($client);

        $this->requestPure($client, $loginLink);
        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'), false);
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
