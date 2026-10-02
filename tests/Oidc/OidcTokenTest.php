<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Oidc;

use App\Entity\User;
use App\Oidc\OidcToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcToken::class)]
class OidcTokenTest extends TestCase
{
    public function testToken(): void
    {
        $user = new User();
        $user->setUserIdentifier('foo@example.com');

        $sut = new OidcToken($user, 'secured_area', ['ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame($user, $sut->getUser());
        self::assertEquals('foo@example.com', $sut->getUserIdentifier());
        self::assertContains('ROLE_USER', $sut->getRoleNames());
        self::assertContains('ROLE_ADMIN', $sut->getRoleNames());
    }

    public function testAttributes(): void
    {
        $user = new User();
        $user->setUserIdentifier('foo@example.com');

        $sut = new OidcToken($user, 'secured_area', $user->getRoles());
        $sut->setAttributes(['email' => 'foo@example.com', 'groups' => ['Admins']]);

        self::assertSame(['email' => 'foo@example.com', 'groups' => ['Admins']], $sut->getAttributes());
        self::assertSame('foo@example.com', $sut->getAttribute('email'));
    }
}
