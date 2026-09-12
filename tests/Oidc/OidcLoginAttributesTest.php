<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Oidc;

use App\Oidc\OidcLoginAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcLoginAttributes::class)]
class OidcLoginAttributesTest extends TestCase
{
    public function testAttributesAccess(): void
    {
        $sut = new OidcLoginAttributes();
        $sut->setAttributes([
            'email' => 'foo@example.com',
            'groups' => ['Sales', 'Admins'],
        ]);

        self::assertSame([
            'email' => 'foo@example.com',
            'groups' => ['Sales', 'Admins'],
        ], $sut->getAttributes());
        self::assertTrue($sut->hasAttribute('email'));
        self::assertSame('foo@example.com', $sut->getAttribute('email'));
        self::assertTrue($sut->hasAttribute('groups'));
        self::assertSame(['Sales', 'Admins'], $sut->getAttribute('groups'));
        self::assertFalse($sut->hasAttribute('missing'));
    }

    public function testGetAttributeThrowsExceptionForUnknownAttribute(): void
    {
        $sut = new OidcLoginAttributes();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This OIDC login has no "missing" attribute.');

        $sut->getAttribute('missing');
    }

    public function testUserIdentifierAccess(): void
    {
        $sut = new OidcLoginAttributes();

        self::assertNull($sut->getUserIdentifier());

        $sut->setUserIdentifier('john.doe@example.com');
        self::assertSame('john.doe@example.com', $sut->getUserIdentifier());

        $sut->setUserIdentifier(null);
        self::assertNull($sut->getUserIdentifier());
    }
}
