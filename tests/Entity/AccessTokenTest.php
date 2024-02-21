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
class AccessTokenTest extends AbstractEntityTest
{
    public function testDefaultValues(): void
    {
        $user = new User();
        $sut = new AccessToken($user, 'foo');

        $this->assertNull($sut->getId());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getExpiresAt());
        $this->assertNull($sut->getLastUsage());
        $this->assertSame('foo', $sut->getToken());
        $this->assertSame($user, $sut->getUser());
        $this->assertTrue($sut->isValid());

        $sut->setName('bar');
        $this->assertSame('bar', $sut->getName());

        $dateTime = new \DateTimeImmutable('-1 year');
        $sut->setLastUsage($dateTime);
        $this->assertSame($dateTime, $sut->getLastUsage());

        $dateTime = new \DateTimeImmutable('-1 month');
        $sut->setExpiresAt($dateTime);
        $this->assertSame($dateTime, $sut->getExpiresAt());
    }

    public function testIsValid(): void
    {
        $user = new User();
        $sut = new AccessToken($user, 'foo');
        $sut->setExpiresAt(new \DateTimeImmutable('-1 day'));
        $this->assertFalse($sut->isValid());
    }
}
