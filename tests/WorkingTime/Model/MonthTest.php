<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Model;

use App\Entity\User;
use App\Entity\WorkingTime;
use App\WorkingTime\Model\Day;
use App\WorkingTime\Model\Month;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Model\Month
 */
class MonthTest extends TestCase
{
    public function testDefaults(): void
    {
        $user = new User();
        $user->setUsername('foo-bar');

        $months = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        foreach($months as $key => $days) {
            $index = ++$key;
            $monthKey = ($index < 10) ? '0' . $index : $index;
            $date = new \DateTimeImmutable(sprintf('2020-%s-25 13:00:00', $monthKey));
            $month = new Month($date, $user);
            self::assertEquals(sprintf('2020-%s-25', $monthKey), $month->getMonth()->format('Y-m-d'));
            self::assertCount($days, $month->getDays());
            self::assertFalse($month->isLocked());
            self::assertEquals(0, $month->getActualTime());
            self::assertEquals(0, $month->getExpectedTime(new \DateTimeImmutable('2035-01-02')));
            self::assertNull($month->getLockDate());
            self::assertNull($month->getLockedBy());
            foreach ($month->getDays() as $day) {
                self::assertInstanceOf(Day::class, $day);
                self::assertNull($day->getWorkingTime());

                $duration = rand(0, 28000);
                $expected = rand(0, 28000);

                $workingTime = new WorkingTime(new User(), $day->getDay());
                $workingTime->setExpectedTime($expected);
                $workingTime->setActualTime($duration);
                $day->setWorkingTime($workingTime);

                self::assertSame($workingTime, $day->getWorkingTime());
                self::assertEquals($expected, $day->getWorkingTime()->getExpectedTime());
                self::assertEquals($duration, $day->getWorkingTime()->getActualTime());
            }
            self::assertFalse($month->isLocked());

            foreach ($month->getDays() as $day) {
                $workingTime = $day->getWorkingTime();
                self::assertNotNull($workingTime);
                $workingTime->setApprovedAt(new \DateTimeImmutable());
            }
            self::assertTrue($month->isLocked());

            $day = $month->getDays()[5];
            $wt = $day->getWorkingTime();
            self::assertNotNull($wt);
            $day->setWorkingTime(null);
            self::assertFalse($month->isLocked());

            $day->setWorkingTime($wt);
            self::assertTrue($month->isLocked());

            $wt->setApprovedAt(null);
            self::assertFalse($month->isLocked());
        }
    }
}
