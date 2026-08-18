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
use PHPUnit\Framework\Attributes\Group;
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
    private function startTwoFactorLogin(HttpKernelBrowser $client): void
    {
        $this->request($client, '/login');
        self::assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('body form')->form();
        $client->submit($form, [
            '_username' => UserFixtures::USERNAME_USER,
            '_password' => UserFixtures::DEFAULT_PASSWORD,
        ]);

        $this->assertIsRedirect($client); // redirect to root URL
        $client->followRedirect();

        $this->assertIsRedirect($client, $this->createUrl(self::URL_2FA));
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
}
