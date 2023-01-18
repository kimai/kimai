<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class ProfileManager
{
    public const SESSION_PROFILE = 'datatable_profile';
    public const PROFILE_DESKTOP = 'desktop';
    public const PROFILE_MOBILE = 'mobile';
    public const COOKIE_PROFILE = 'K2P';

    public function __construct()
    {
    }

    public function isValidProfile(string $profile): bool
    {
        return \in_array($profile, [self::PROFILE_DESKTOP, self::PROFILE_MOBILE]);
    }

    public function getDatatableName(string $dataTable, ?string $profile = null): string
    {
        if (empty($profile) || $profile === self::PROFILE_DESKTOP) {
            return $dataTable;
        }

        return trim($dataTable . '_' . $profile);
    }

    /**
     * Always returns a valid profile name (default: desktop).
     *
     * @param string $profile
     * @return string
     */
    public function getProfile(string $profile): string
    {
        if (!\in_array($profile, [self::PROFILE_DESKTOP, self::PROFILE_MOBILE])) {
            return self::PROFILE_DESKTOP;
        }

        return $profile;
    }

    public function setProfile(SessionInterface $session, string $profile): void
    {
        if ($profile === self::PROFILE_MOBILE) {
            $session->set(self::SESSION_PROFILE, $profile);
        } else {
            $session->remove(self::SESSION_PROFILE);
        }
    }

    /**
     * Always returns a valid profile name (default: desktop).
     *
     * @param Request $request
     * @return string
     */
    public function getProfileFromCookie(Request $request): string
    {
        $profile = $request->cookies->get(self::COOKIE_PROFILE, self::PROFILE_DESKTOP);

        return $this->getProfile($profile);
    }

    /**
     * Always returns a valid profile name (default: desktop).
     *
     * @param Session $session
     * @return string
     */
    public function getProfileFromSession(Session $session): string
    {
        $profile = $session->get(self::SESSION_PROFILE, self::PROFILE_DESKTOP);

        return $this->getProfile($profile);
    }
}
