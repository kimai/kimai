<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessMapInterface;

/**
 * Regression test for the firewall-level access_control of localized URLs.
 *
 * The access_control paths are plain regular expressions, the route placeholder {_locale}
 * is not replaced there. Using it caused all localized rules to never match, so the
 * ROLE_USER fallback for /{_locale}/* was silently lost.
 */
#[Group('integration')]
class LocaleAccessControlTest extends WebTestCase
{
    /**
     * @return array<string>
     */
    private function getAttributes(string $path): array
    {
        $accessMap = self::getContainer()->get('security.access_map');
        self::assertInstanceOf(AccessMapInterface::class, $accessMap);

        [$attributes] = $accessMap->getPatterns(Request::create($path));

        return $attributes ?? [];
    }

    public function testAllEnabledLocalesRequireUser(): void
    {
        self::bootKernel();

        $locales = self::getContainer()->getParameter('kimai_translated_locales');
        self::assertIsArray($locales);
        self::assertNotEmpty($locales);

        foreach ($locales as $locale) {
            self::assertSame(['ROLE_USER'], $this->getAttributes('/' . $locale . '/timesheet/'), 'Missing ROLE_USER access_control for locale: ' . $locale);
            self::assertSame(['PUBLIC_ACCESS'], $this->getAttributes('/' . $locale), 'Missing PUBLIC_ACCESS access_control for locale homepage: ' . $locale);
        }
    }

    /**
     * @return iterable<array{0: string}>
     */
    public static function getProtectedUrls(): iterable
    {
        yield ['/en/'];
        yield ['/en/about'];
        yield ['/en/timesheet/'];
        yield ['/en/admin/user/'];
        yield ['/de/dashboard/'];
        yield ['/de_CH/profile/'];
        yield ['/pt_BR/invoice/'];
        yield ['/nb_NO/reporting/'];
        yield ['/zh_Hant_TW/timesheet/'];
        yield ['/en/logout'];
    }

    #[DataProvider('getProtectedUrls')]
    public function testLocalizedUrlRequiresUser(string $path): void
    {
        self::bootKernel();

        self::assertSame(['ROLE_USER'], $this->getAttributes($path));
    }

    /**
     * @return iterable<array{0: string}>
     */
    public static function getPublicUrls(): iterable
    {
        yield ['/en'];
        yield ['/zh_Hant_TW'];
        yield ['/en/login'];
        yield ['/en/login_check'];
        yield ['/en/auth/2fa'];
        yield ['/en/auth/link/check'];
        yield ['/en/register/'];
        yield ['/en/resetting/request'];
        yield ['/de_CH/login'];
        yield ['/auth/saml/login'];
        yield ['/login'];
        yield ['/logout'];
    }

    #[DataProvider('getPublicUrls')]
    public function testLocalizedUrlIsPublic(string $path): void
    {
        self::bootKernel();

        self::assertSame(['PUBLIC_ACCESS'], $this->getAttributes($path));
    }

    public function testApiIsNotMatchedByLocaleRules(): void
    {
        self::bootKernel();

        self::assertSame(['IS_AUTHENTICATED_REMEMBERED'], $this->getAttributes('/api'));
        self::assertSame(['IS_AUTHENTICATED_REMEMBERED'], $this->getAttributes('/api/'));
        self::assertSame(['IS_AUTHENTICATED_REMEMBERED'], $this->getAttributes('/api/users/me'));
    }

    /**
     * @return iterable<array{0: string}>
     */
    public static function getAnonymousUrls(): iterable
    {
        yield ['/en/about'];
        yield ['/en/timesheet/'];
        yield ['/en/contract'];
        yield ['/en/admin/user/'];
        yield ['/de/dashboard/'];
    }

    #[DataProvider('getAnonymousUrls')]
    public function testAnonymousRequestRedirectsToLogin(string $path): void
    {
        $client = self::createClient();
        $client->request('GET', $path);

        $response = $client->getResponse();
        self::assertTrue($response->isRedirect(), \sprintf('The URL %s is not protected (%s occurred).', $path, $response->getStatusCode()));
        self::assertStringEndsWith('/login', (string) $response->headers->get('Location'));
    }
}
