<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\WorkingTime;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\WorkingTime
 */
class WorkingTimeTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $user = new User();
        $user->setUsername('bar');
        $date = new \DateTime();
        $sut = new WorkingTime($user, $date);

        self::assertSame($user, $sut->getUser());
        self::assertSame($date, $sut->getDate());

        self::assertNull($sut->getId());

        self::assertEquals(0, $sut->getActualTime());
        self::assertEquals(0, $sut->getExpectedTime());
        self::assertNull($sut->getApprovedAt());
        self::assertNull($sut->getApprovedBy());
        self::assertFalse($sut->isApproved());

        $approvedAt = new \DateTime('2023-01-01 12:00:00', new \DateTimeZone('Europe/Vienna'));
        $approvedBy = new User();
        $approvedBy->setUsername('foo');

        $sut->setApprovedAt($approvedAt);
        $sut->setApprovedBy($approvedBy);
        $sut->setActualTime(5600);
        $sut->setExpectedTime(8600);

        self::assertSame(5600, $sut->getActualTime());
        self::assertSame(8600, $sut->getExpectedTime());
        self::assertSame($approvedAt, $sut->getApprovedAt());
        self::assertSame($approvedBy, $sut->getApprovedBy());
        self::assertTrue($sut->isApproved());
    }
}
