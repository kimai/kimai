<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\ProfileManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

#[CoversClass(ProfileManager::class)]
class ProfileManagerTest extends TestCase
{
    public function testEmpty(): void
    {
        $request = new Request();
        $session = new Session(new MockFileSessionStorage());
        $request->setSession($session);

        $sut = new ProfileManager();
        self::assertEquals(ProfileManager::PROFILE_DESKTOP, $sut->getProfileFromCookie($request));
        self::assertEquals(ProfileManager::PROFILE_DESKTOP, $sut->getProfileFromSession($session));
    }

    /**
     * @return array<int, array<string>>
     */
    public static function getInvalidProfiles(): array
    {
        return [
            ['MOBILE'],
            ['mobilE'],
            ['mobile2'],
            ['mobile2'],
            ['foo'],
            ['mobile '],
            [' desktop'],
            ['DESKTOP'],
        ];
    }

    #[DataProvider('getInvalidProfiles')]
    public function testIsInvalidProfile(string $profile): void
    {
        $sut = new ProfileManager();
        self::assertFalse($sut->isValidProfile($profile));
    }

    /**
     * @return array<int, array<string|null>>
     */
    public static function getDatatableNames(): array
    {
        return [
            ['admin-timesheet_mobile', 'admin-timesheet', 'mobile'],
            ['admin-timesheet_mobile', 'admin-timesheet', ProfileManager::PROFILE_MOBILE],
            ['admin-timesheet', 'admin-timesheet', 'desktop'],
            ['admin-timesheet', 'admin-timesheet', ProfileManager::PROFILE_DESKTOP],
            ['admin-timesheet', 'admin-timesheet', ''],
            ['admin-timesheet_', 'admin-timesheet', ' '],
            ['admin-timesheet_', ' admin-timesheet', ' '],
            ['admin-timesheet', 'admin-timesheet', null],
        ];
    }

    #[DataProvider('getDatatableNames')]
    public function testDatatableName(string $expected, string $datatable, ?string $prefix): void
    {
        $sut = new ProfileManager();
        self::assertEquals($expected, $sut->getDatatableName($datatable, $prefix));
    }

    /**
     * @return array<int, array<string>>
     */
    public static function getProfileNames(): array
    {
        return [
            ['mobile', ProfileManager::PROFILE_MOBILE],
            [' mobile', ProfileManager::PROFILE_DESKTOP],
            ['mobile ', ProfileManager::PROFILE_DESKTOP],
            ['MOBILE', ProfileManager::PROFILE_DESKTOP],
            ['mobilE', ProfileManager::PROFILE_DESKTOP],
            ['mobile2', ProfileManager::PROFILE_DESKTOP],
            ['mobile2', ProfileManager::PROFILE_DESKTOP],
            ['foo', ProfileManager::PROFILE_DESKTOP],
            ['mobile ', ProfileManager::PROFILE_DESKTOP],
            [' desktop', ProfileManager::PROFILE_DESKTOP],
            ['DESKTOP', ProfileManager::PROFILE_DESKTOP],
            ['', ProfileManager::PROFILE_DESKTOP],
        ];
    }

    #[DataProvider('getProfileNames')]
    public function testGetProfile(string $profile, string $expected): void
    {
        $sut = new ProfileManager();
        self::assertEquals($expected, $sut->getProfile($profile));
    }

    public function testSetProfile(): void
    {
        $request = new Request();
        $session = new Session(new MockFileSessionStorage());
        $request->setSession($session);

        self::assertNull($session->get(ProfileManager::SESSION_PROFILE));

        $sut = new ProfileManager();

        $sut->setProfile($session, ProfileManager::PROFILE_DESKTOP);
        self::assertNull($session->get(ProfileManager::SESSION_PROFILE));

        $sut->setProfile($session, ProfileManager::PROFILE_MOBILE);
        self::assertNotNull($session->get(ProfileManager::SESSION_PROFILE));
        self::assertEquals(ProfileManager::PROFILE_MOBILE, $session->get(ProfileManager::SESSION_PROFILE));

        $sut->setProfile($session, 'foo');
        self::assertNull($session->get(ProfileManager::SESSION_PROFILE));
    }

    /**
     * @return array<int, array<string>>
     */
    public static function getCookieProfiles(): array
    {
        return [
            ['mobile', ProfileManager::PROFILE_MOBILE],
            ['desktop', ProfileManager::PROFILE_DESKTOP],
            ['foo', ProfileManager::PROFILE_DESKTOP],
            ['MOBILE', ProfileManager::PROFILE_DESKTOP],
            ['', ProfileManager::PROFILE_DESKTOP],
        ];
    }

    #[DataProvider('getCookieProfiles')]
    public function testGetProfileFromCookie(string $cookieValue, string $expected): void
    {
        $request = new Request();
        self::assertFalse($request->cookies->has(ProfileManager::COOKIE_PROFILE));

        $session = new Session(new MockFileSessionStorage());
        $request->setSession($session);

        $request->cookies->set(ProfileManager::COOKIE_PROFILE, $cookieValue);

        $sut = new ProfileManager();
        $profile = $sut->getProfileFromCookie($request);
        self::assertEquals($expected, $profile);
    }

    /**
     * @return array<int, array<string>>
     */
    public static function getSessionProfiles(): array
    {
        return [
            ['mobile', ProfileManager::PROFILE_MOBILE],
            ['desktop', ProfileManager::PROFILE_DESKTOP],
            ['foo', ProfileManager::PROFILE_DESKTOP],
            ['MOBILE', ProfileManager::PROFILE_DESKTOP],
            ['', ProfileManager::PROFILE_DESKTOP],
        ];
    }

    #[DataProvider('getSessionProfiles')]
    public function testGetProfileFromSession(string $sessionValue, string $expected): void
    {
        $request = new Request();
        self::assertFalse($request->cookies->has(ProfileManager::COOKIE_PROFILE));

        $session = new Session(new MockFileSessionStorage());
        $request->setSession($session);

        $session->set(ProfileManager::SESSION_PROFILE, $sessionValue);

        $sut = new ProfileManager();
        $profile = $sut->getProfileFromSession($session);
        self::assertEquals($expected, $profile);
    }
}
