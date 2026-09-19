<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Security;

use App\DataFixtures\UserFixtures;
use App\Tests\Controller\AbstractControllerBaseTestCase;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * Makes sure the two-factor authentication form is usable, even if the
 * login form was deactivated (SAML only setup without local password login).
 */
#[Group('integration')]
class TwoFactorLoginTest extends AbstractControllerBaseTestCase
{
    private const TOTP_SECRET = 'JBSWY3DPEHPK3PXP';
    private const URL_2FA = '/auth/2fa';
    private const URL_ADMIN_ONLY = '/admin/user/';
    private const REMEMBER_ME_COOKIE = 'KIMAI_REMEMBER';

    private function activateTwoFactor(string $username): void
    {
        $em = $this->getEntityManager();
        $user = $this->getUserByName($username);
        $user->setTotpSecret(self::TOTP_SECRET);
        $user->enableTotpAuthentication();
        $em->persist($user);
        $em->flush();
    }

    /**
     * Logs in with username and password, which is only the first step:
     * the user is not authenticated before the TOTP code was validated.
     */
    private function startTwoFactorLogin(HttpKernelBrowser $client, string $username = UserFixtures::USERNAME_USER): void
    {
        $this->submitLoginForm($client, $username);

        $this->assertIsRedirect($client); // redirect to root URL
        $client->followRedirect();

        $this->assertIsRedirect($client, $this->createUrl(self::URL_2FA));
    }

    /**
     * Submits the login (or unlock) form with the given credentials, the CSRF token is taken from the form.
     */
    private function submitLoginForm(HttpKernelBrowser $client, string $username, string $password = UserFixtures::DEFAULT_PASSWORD): void
    {
        $this->request($client, '/login');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('body form')->form();
        // the unlock form uses a hidden field, which can be replaced by an attacker
        $form->disableValidation();
        $client->submit($form, [
            '_username' => $username,
            '_password' => $password,
        ]);
    }

    private function completeTwoFactorLogin(HttpKernelBrowser $client): void
    {
        $this->request($client, self::URL_2FA);
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('body form')->form();
        $client->submit($form, [
            '_auth_code' => TOTP::createFromSecret(self::TOTP_SECRET)->now(),
        ]);

        $this->assertIsRedirect($client);
    }

    /**
     * Simulates a browser whose session expired, but which still has the remember-me cookie.
     */
    private function expireSession(HttpKernelBrowser $client): void
    {
        self::assertNotNull($client->getCookieJar()->get(self::REMEMBER_ME_COOKIE), 'Missing remember-me cookie');
        // the session cookie is named differently in the test environment (mock session storage)
        foreach ($client->getCookieJar()->all() as $cookie) {
            if ($cookie->getName() !== self::REMEMBER_ME_COOKIE) {
                $client->getCookieJar()->expire($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            }
        }
    }

    private function assertUnlockFormIsShown(HttpKernelBrowser $client, string $username): void
    {
        $this->request($client, '/login');
        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('<input type="hidden" name="_username" value="' . $username . '">', $content);
    }

    private function assertIsFullyAuthenticated(HttpKernelBrowser $client): void
    {
        $this->request($client, '/homepage');
        $this->assertIsRedirect($client);
        self::assertStringNotContainsString(self::URL_2FA, (string) $client->getResponse()->headers->get('Location'));
        self::assertStringNotContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    /**
     * The super-admin is the victim in the attack scenarios: if the attack succeeds the client
     * would gain access to the admin-only pages.
     */
    private function assertHasNoAdminAccess(HttpKernelBrowser $client): void
    {
        $this->request($client, self::URL_ADMIN_ONLY);
        self::assertNotEquals(200, $client->getResponse()->getStatusCode(), 'Attacker gained access to the victims account');
    }

    public function testTwoFactorFormIsRenderedWithActiveLoginForm(): void
    {
        $client = self::createClient();
        $this->activateTwoFactor(UserFixtures::USERNAME_USER);
        $this->startTwoFactorLogin($client);

        $this->request($client, self::URL_2FA);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('<input id="_auth_code" type="text" name="_auth_code"', $content);
    }

    /**
     * @see https://github.com/kimai/kimai/pull/6136
     */
    public function testTwoFactorFormIsRenderedWithDeactivatedLoginForm(): void
    {
        $client = self::createClient();
        $this->activateTwoFactor(UserFixtures::USERNAME_USER);
        $this->startTwoFactorLogin($client);

        // company uses SAML and forbids local password logins: the login form is hidden ...
        $this->setSystemConfiguration('saml.activate', true);
        $this->setSystemConfiguration('user.login', false);

        $this->request($client, self::URL_2FA);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        // ... but the 2FA code form has to stay visible, otherwise the user cannot finish the login
        self::assertStringContainsString('<input id="_auth_code" type="text" name="_auth_code"', $content);
        self::assertStringContainsString('/auth/2fa_check', $content);

        // the deactivated login form is still hidden
        self::assertStringNotContainsString('name="_password"', $content);
        self::assertStringNotContainsString('/login_check', $content);
    }

    /**
     * A user who passed the 2FA and is remembered via cookie does not need to enter the TOTP code again,
     * when re-entering the password on the "unlock" form (e.g. after the session expired).
     */
    public function testRememberedUserCanUnlockWithoutTwoFactor(): void
    {
        $client = self::createClient();
        $this->activateTwoFactor(UserFixtures::USERNAME_USER);
        $this->startTwoFactorLogin($client);
        $this->completeTwoFactorLogin($client);
        $this->assertIsFullyAuthenticated($client);

        $this->expireSession($client);
        $this->assertUnlockFormIsShown($client, UserFixtures::USERNAME_USER);

        $this->submitLoginForm($client, UserFixtures::USERNAME_USER);
        $this->assertIsRedirect($client);
        $client->followRedirect();

        // no 2FA form, the user is logged in
        $this->assertIsFullyAuthenticated($client);
    }

    /**
     * A remembered user must be able to log in with another account (e.g. shared computer), the
     * remembered state of the previous user is simply replaced.
     */
    public function testRememberedUserCanLoginAsOtherUser(): void
    {
        $client = self::createClient();

        $this->submitLoginForm($client, UserFixtures::USERNAME_USER);
        $this->assertIsRedirect($client);
        $client->followRedirect();
        $this->assertIsFullyAuthenticated($client);

        $this->expireSession($client);
        $this->assertUnlockFormIsShown($client, UserFixtures::USERNAME_USER);

        $this->submitLoginForm($client, UserFixtures::USERNAME_SUPER_ADMIN);
        $this->assertIsRedirect($client);
        $client->followRedirect();
        $this->assertIsFullyAuthenticated($client);

        // the admin-only page requires full authentication and admin permissions
        $this->request($client, self::URL_ADMIN_ONLY);
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * Regression test: the remember-me cookie of one account must not skip the 2FA of another account.
     *
     * Attack: login as attacker, delete the session cookie (keep the remember-me cookie), open the "unlock" form
     * and replace the hidden username with the victims username, whose password is known to the attacker.
     */
    public function testRememberedUserCannotSkipTwoFactorOfOtherUser(): void
    {
        $client = self::createClient();
        $this->activateTwoFactor(UserFixtures::USERNAME_SUPER_ADMIN);

        // attacker logs in with his own account (no 2FA) and receives a remember-me cookie
        $this->submitLoginForm($client, UserFixtures::USERNAME_USER);
        $this->assertIsRedirect($client);
        $client->followRedirect();
        $this->assertIsFullyAuthenticated($client);

        $this->expireSession($client);
        $this->assertUnlockFormIsShown($client, UserFixtures::USERNAME_USER);

        // now try to login as the victim, using the "unlock" form of the attacker:
        // the password is correct, so the login succeeds, but the 2FA of the victim must be enforced
        $this->submitLoginForm($client, UserFixtures::USERNAME_SUPER_ADMIN);
        $this->assertIsRedirect($client); // redirect to root URL
        $client->followRedirect();
        $this->assertIsRedirect($client, $this->createUrl(self::URL_2FA));

        $this->assertHasNoAdminAccess($client);
    }

    /**
     * A fully authenticated session of one account must not be usable to log in another account.
     *
     * The login form is not shown to authenticated users, so the attacker fetches the CSRF token before the login
     * and posts the victims credentials afterward. This is already prevented, because Symfony clears the CSRF
     * tokens on login (see SessionAuthenticationStrategy) - this test makes sure it stays that way.
     */
    public function testAuthenticatedUserCannotReuseCsrfTokenToLoginAsOtherUser(): void
    {
        $client = self::createClient();
        $this->activateTwoFactor(UserFixtures::USERNAME_SUPER_ADMIN);

        $this->request($client, '/login');
        $form = $client->getCrawler()->filter('body form')->form();
        $csrfField = $form->get('_csrf_token');
        self::assertInstanceOf(FormField::class, $csrfField);
        $csrfToken = $csrfField->getValue();
        self::assertIsString($csrfToken);
        self::assertNotEmpty($csrfToken);

        // attacker logs in with his own account (no 2FA)
        $client->submit($form, [
            '_username' => UserFixtures::USERNAME_USER,
            '_password' => UserFixtures::DEFAULT_PASSWORD,
        ]);
        $this->assertIsRedirect($client);
        $client->followRedirect();
        $this->assertIsFullyAuthenticated($client);

        // now post the victims credentials, while still being logged in as attacker
        $this->request($client, '/login_check', 'POST', [
            '_username' => UserFixtures::USERNAME_SUPER_ADMIN,
            '_password' => UserFixtures::DEFAULT_PASSWORD,
            '_csrf_token' => $csrfToken,
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/login'));

        $this->assertHasNoAdminAccess($client);
    }
}
