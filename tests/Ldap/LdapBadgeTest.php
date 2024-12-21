<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Ldap\LdapBadge;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Ldap\LdapBadge
 */
class LdapBadgeTest extends TestCase
{
    public function testMarkResolvedSetsResolvedToTrue(): void
    {
        $badge = new LdapBadge();
        $badge->markResolved();
        self::assertTrue($badge->isResolved());
    }

    public function testIsResolvedReturnsFalseInitially(): void
    {
        $badge = new LdapBadge();
        self::assertFalse($badge->isResolved());
    }
}
