<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Saml\SamlLoginAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SamlLoginAttributes::class)]
class SamlLoginAttributesTest extends TestCase
{
    public function testAttributesAccess(): void
    {
        $sut = new SamlLoginAttributes();
        $sut->setAttributes([
            'mail' => ['foo@example.com'],
            'department' => 'Sales',
        ]);

        self::assertSame([
            'mail' => ['foo@example.com'],
            'department' => 'Sales',
        ], $sut->getAttributes());
        self::assertTrue($sut->hasAttribute('mail'));
        self::assertSame(['foo@example.com'], $sut->getAttribute('mail'));
        self::assertTrue($sut->hasAttribute('department'));
        self::assertSame('Sales', $sut->getAttribute('department'));
        self::assertFalse($sut->hasAttribute('missing'));
    }

    public function testGetAttributeThrowsExceptionForUnknownAttribute(): void
    {
        $sut = new SamlLoginAttributes();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This SAML login has no "missing" attribute.');

        $sut->getAttribute('missing');
    }

    public function testUserIdentifierAccess(): void
    {
        $sut = new SamlLoginAttributes();

        self::assertNull($sut->getUserIdentifier());

        $sut->setUserIdentifier('john.doe@example.com');
        self::assertSame('john.doe@example.com', $sut->getUserIdentifier());

        $sut->setUserIdentifier(null);
        self::assertNull($sut->getUserIdentifier());
    }
}
