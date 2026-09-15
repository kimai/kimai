<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Oidc;

use App\Oidc\OidcBadge;
use App\Oidc\OidcLoginAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcBadge::class)]
class OidcBadgeTest extends TestCase
{
    public function testBadge(): void
    {
        $attributes = new OidcLoginAttributes();
        $attributes->setUserIdentifier('foo@example.com');

        $sut = new OidcBadge($attributes);

        self::assertSame($attributes, $sut->getOidcLoginAttributes());
        self::assertTrue($sut->isResolved());
    }
}
