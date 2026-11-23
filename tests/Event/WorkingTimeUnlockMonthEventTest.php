<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\WorkingTimeUnlockMonthEvent;
use App\WorkingTime\Model\Month;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkingTimeUnlockMonthEvent::class)]
class WorkingTimeUnlockMonthEventTest extends TestCase
{
    public function testGetter(): void
    {
        $user = new User();
        $approver = new User();
        $approvalMonth = new Month(new \DateTimeImmutable('2023-02-10'), $user);

        $sut = new WorkingTimeUnlockMonthEvent($approvalMonth, $approver);

        self::assertSame($approvalMonth, $sut->getMonth());
        self::assertSame($approver, $sut->getUnlockedBy());
    }
}
