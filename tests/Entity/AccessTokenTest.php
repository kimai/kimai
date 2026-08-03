<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\AccessToken;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AccessToken::class)]
class AccessTokenTest extends AbstractEntityTestCase
{
    public function testDefaultValues(): void
    {
        $user = new User();
        $sut = new AccessToken($user, 'foo');

        self::assertNull($sut->getId());
        self::assertNull($sut->getName());
        self::assertNull($sut->getExpiresAt());
        self::assertNull($sut->getLastUsage());
        self::assertSame('foo', $sut->getToken());
        self::assertSame($user, $sut->getUser());
        self::assertTrue($sut->isValid());
        self::assertNull($sut->getScopes());
        self::assertTrue($sut->isLegacy());
        // a legacy token (no scopes) is allowed to do everything
        self::assertTrue($sut->hasScope('timesheet:read'));

        $sut->setName('bar');
        self::assertSame('bar', $sut->getName());

        $dateTime = new \DateTimeImmutable('-1 year');
        $sut->setLastUsage($dateTime);
        self::assertSame($dateTime, $sut->getLastUsage());

        $dateTime = new \DateTimeImmutable('-1 month');
        $sut->setExpiresAt($dateTime);
        self::assertSame($dateTime, $sut->getExpiresAt());
    }

    public function testIsValid(): void
    {
        $user = new User();
        $sut = new AccessToken($user, 'foo');
        $sut->setExpiresAt(new \DateTimeImmutable('-1 day'));
        self::assertFalse($sut->isValid());
    }

    public function testScopes(): void
    {
        $sut = new AccessToken(new User(), 'foo');

        $sut->setScopes(['timesheet:read', 'timesheet:read', 'customer:create', '']);
        // normalized: unique, re-indexed, no empty strings
        self::assertSame(['timesheet:read', 'customer:create'], $sut->getScopes());
        self::assertFalse($sut->isLegacy());
        self::assertTrue($sut->hasScope('timesheet:read'));
        self::assertTrue($sut->hasScope('customer:create'));
        self::assertFalse($sut->hasScope('customer:delete'));

        // an empty scope set restricts everything
        $sut->setScopes([]);
        self::assertSame([], $sut->getScopes());
        self::assertFalse($sut->isLegacy());
        self::assertFalse($sut->hasScope('timesheet:read'));

        // resetting to null makes it a legacy token again
        $sut->setScopes(null);
        self::assertNull($sut->getScopes());
        self::assertTrue($sut->isLegacy());
        self::assertTrue($sut->hasScope('anything:read'));
    }

    public function testClone(): void
    {
        $user = new User();
        $sut = new AccessToken($user, 'foo');
        $this->assertCloneResetsId($sut);
    }
}
