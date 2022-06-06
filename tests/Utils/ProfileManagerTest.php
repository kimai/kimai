<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\ProfileManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

/**
 * @covers \App\Utils\ProfileManager
 */
class ProfileManagerTest extends TestCase
{
    public function testEmpty()
    {
        $request = new Request();
        $session = new Session(new MockFileSessionStorage());
        $request->setSession($session);

        $sut = new ProfileManager();
        self::assertEquals(ProfileManager::PROFILE_DESKTOP, $sut->getProfileFromCookie($request));
        self::assertEquals(ProfileManager::PROFILE_DESKTOP, $sut->getProfileFromSession($session));
    }

    public function getInvalidProfiles()
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

    /**
     * @dataProvider getInvalidProfiles
     */
    public function testIsInvalidProfile(string $profile)
    {
        $sut = new ProfileManager();
        self::assertFalse($sut->isValidProfile($profile));
    }

    public function getDatatableNames()
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

    /**
     * @dataProvider getDatatableNames
     */
    public function testDatatableName(string $expected, string $datatable, ?string $prefix)
    {
        $sut = new ProfileManager();
        self::assertEquals($expected, $sut->getDatatableName($datatable, $prefix));
    }

    public function getProfileNames()
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

    /**
     * @dataProvider getProfileNames
     */
    public function testGetProfile(string $profile, string $expected)
    {
        $sut = new ProfileManager();
        self::assertEquals($expected, $sut->getProfile($profile));
    }

    public function testSetProfile()
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

    public function getCookieProfiles()
    {
        return [
            ['mobile', ProfileManager::PROFILE_MOBILE],
            ['desktop', ProfileManager::PROFILE_DESKTOP],
            ['foo', ProfileManager::PROFILE_DESKTOP],
            ['MOBILE', ProfileManager::PROFILE_DESKTOP],
            ['', ProfileManager::PROFILE_DESKTOP],
        ];
    }

    /**
     * @dataProvider getCookieProfiles
     */
    public function testGetProfileFromCookie(string $cookieValue, string $expected)
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

    public function getSessionProfiles()
    {
        return [
            ['mobile', ProfileManager::PROFILE_MOBILE],
            ['desktop', ProfileManager::PROFILE_DESKTOP],
            ['foo', ProfileManager::PROFILE_DESKTOP],
            ['MOBILE', ProfileManager::PROFILE_DESKTOP],
            ['', ProfileManager::PROFILE_DESKTOP],
        ];
    }

    /**
     * @dataProvider getSessionProfiles
     */
    public function testGetProfileFromSession(string $sessionValue, string $expected)
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
