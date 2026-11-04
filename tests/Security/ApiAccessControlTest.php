<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessMapInterface;

/**
 * Regression test for the 2FA-API-bypass security advisory.
 *
 * The firewall-level access_control for ^/api was raised from IS_AUTHENTICATED
 * to IS_AUTHENTICATED_REMEMBERED so that a TwoFactorToken (which only satisfies
 * IS_AUTHENTICATED) can no longer reach any /api/* route, while remember_me
 * sessions used by the web frontend continue to work.
 */
#[Group('integration')]
class ApiAccessControlTest extends KernelTestCase
{
    public function testApiRouteRequiresAuthenticatedRemembered(): void
    {
        self::bootKernel();
        $accessMap = self::getContainer()->get('security.access_map');
        self::assertInstanceOf(AccessMapInterface::class, $accessMap);

        [$attributes] = $accessMap->getPatterns(Request::create('/api/users/me'));

        self::assertIsArray($attributes);
        self::assertContains(
            'IS_AUTHENTICATED_REMEMBERED',
            $attributes,
            'API access_control must require IS_AUTHENTICATED_REMEMBERED to keep a TwoFactorToken from reaching /api/*'
        );
        self::assertNotContains(
            'IS_AUTHENTICATED',
            $attributes,
            'API access_control must not fall back to IS_AUTHENTICATED, which a TwoFactorToken satisfies'
        );
    }
}
