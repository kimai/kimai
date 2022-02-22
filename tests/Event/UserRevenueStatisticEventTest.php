<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\UserRevenueStatisticEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\UserRevenueStatisticEvent
 */
class UserRevenueStatisticEventTest extends TestCase
{
    public function testDefaultValues()
    {
        $user = new User();
        $sut = new UserRevenueStatisticEvent($user, null, null);

        self::assertSame($user, $sut->getUser());
        self::assertNull($sut->getEnd());
        self::assertNull($sut->getBegin());
        self::assertSame(0.0, $sut->getRevenue());

        $end = new \DateTime();
        $begin = new \DateTime();

        $sut = new UserRevenueStatisticEvent($user, $begin, $end);

        self::assertSame($user, $sut->getUser());
        self::assertSame($begin, $sut->getBegin());
        self::assertSame($end, $sut->getEnd());
        self::assertSame(0.0, $sut->getRevenue());

        $sut->addRevenue(13.45);
        $sut->addRevenue(6.55);
        $sut->addRevenue(111);
        $sut->addRevenue(2222.22);

        self::assertSame(2353.22, $sut->getRevenue());
    }
}
