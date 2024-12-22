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

/**
 * @covers \App\Entity\AccessToken
 */
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
}
