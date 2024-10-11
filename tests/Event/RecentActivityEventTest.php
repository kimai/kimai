<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\RecentActivityEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\RecentActivityEvent
 */
class RecentActivityEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $tmp1 = new Timesheet();
        $tmp2 = new Timesheet();
        $tmp3 = new Timesheet();
        $tmp4 = new Timesheet();
        $tmp5 = new Timesheet();
        $tmp6 = new Timesheet();
        $tmp7 = new Timesheet();

        $timesheets = [
            $tmp1,
            $tmp2,
            $tmp3,
            $tmp4,
            $tmp5,
        ];

        $sut = new RecentActivityEvent($user, $timesheets);
        self::assertCount(5, $sut->getRecentActivities());
        self::assertEquals($user, $sut->getUser());
        self::assertEquals([$tmp1, $tmp2, $tmp3, $tmp4, $tmp5], $sut->getRecentActivities());
        self::assertFalse($sut->removeRecentActivity($tmp6));
        self::assertTrue($sut->removeRecentActivity($tmp3));
        self::assertInstanceOf(RecentActivityEvent::class, $sut->addRecentActivity($tmp6));
        self::assertFalse($sut->removeRecentActivity($tmp7));
        self::assertEquals([$tmp1, $tmp2, $tmp4, $tmp5, $tmp6], $sut->getRecentActivities());
    }
}
