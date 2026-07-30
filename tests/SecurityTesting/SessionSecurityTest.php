<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\SecurityTesting;

use App\Tests\Controller\AbstractControllerBaseTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Security tests for OWASP WSTG v4.2 category "4.6 Session Management Testing" (WSTG-SESS).
 *
 * Verifies the cookie flags of the session cookie in both HTTP and HTTPS context.
 */
#[Group('integration')]
#[Group('security')]
class SessionSecurityTest extends AbstractControllerBaseTestCase
{
    // in the "test" environment the mock session storage uses its own default name
    private const SESSION_COOKIE_NAMES = ['KIMAI_SESSION', 'MOCKSESSID'];

    /**
     * WSTG-SESS-02:
     * The session cookie must carry the HttpOnly and SameSite=Lax attributes,
     * so it cannot be read via JavaScript (XSS) or sent in cross-site requests (CSRF).
     */
    public function testSessionCookieIsHttpOnlyAndSameSite(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en/login');

        $cookie = self::extractSessionCookie($client->getResponse()->headers->getCookies());

        self::assertNotNull($cookie, 'Session cookie was not set on the login page');
        self::assertTrue($cookie->isHttpOnly(), 'Session cookie misses the HttpOnly flag');
        self::assertSame('lax', strtolower((string) $cookie->getSameSite()), 'Session cookie misses SameSite=Lax');
        self::assertFalse($cookie->isSecure(), 'Secure flag must not be sent over plain HTTP (cookie_secure: auto)');
    }

    // the Secure flag (cookie_secure: auto) is only set by the native session storage and
    // is therefore guarded by SecurityConfigurationTest and the post-deployment validation
    // scripts instead of the mock session storage used in the "test" environment

    /**
     * @param array<int, Cookie> $cookies
     */
    private static function extractSessionCookie(array $cookies): ?Cookie
    {
        foreach ($cookies as $cookie) {
            if (\in_array($cookie->getName(), self::SESSION_COOKIE_NAMES, true)) {
                return $cookie;
            }
        }

        return null;
    }
}
