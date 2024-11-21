<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Calculator;

use App\Entity\User;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Calculator\WorkingTimeCalculatorDay
 */
class WorkingTimeCalculatorDayTest extends TestCase
{
    public function testDefaults(): void
    {
        $monday = new \DateTimeImmutable('monday last week 12:00:00');
        $tuesday = $monday->modify('+1 day');
        $wednesday = $tuesday->modify('+1 day');
        $thursday = $wednesday->modify('+1 day');
        $friday = $thursday->modify('+1 day');
        $saturday = $friday->modify('+1 day');
        $sunday = $saturday->modify('+1 day');

        // verify not nullable
        $sut = new WorkingTimeCalculatorDay(new User());
        self::assertFalse($sut->isWorkDay($monday));
        self::assertFalse($sut->isWorkDay($tuesday));
        self::assertFalse($sut->isWorkDay($wednesday));
        self::assertFalse($sut->isWorkDay($thursday));
        self::assertFalse($sut->isWorkDay($friday));
        self::assertFalse($sut->isWorkDay($saturday));
        self::assertFalse($sut->isWorkDay($sunday));

        self::assertEquals(0, $sut->getWorkHoursForDay($monday));
        self::assertEquals(0, $sut->getWorkHoursForDay($tuesday));
        self::assertEquals(0, $sut->getWorkHoursForDay($wednesday));
        self::assertEquals(0, $sut->getWorkHoursForDay($thursday));
        self::assertEquals(0, $sut->getWorkHoursForDay($friday));
        self::assertEquals(0, $sut->getWorkHoursForDay($saturday));
        self::assertEquals(0, $sut->getWorkHoursForDay($sunday));

        $user = new User();
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY, 0);
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY, 3600);
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY, 0);
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY, 7200);
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY, 0);
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY, 1800);
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY, 9000);

        $sut = new WorkingTimeCalculatorDay($user);

        self::assertFalse($sut->isWorkDay($monday));
        self::assertTrue($sut->isWorkDay($tuesday));
        self::assertFalse($sut->isWorkDay($wednesday));
        self::assertTrue($sut->isWorkDay($thursday));
        self::assertFalse($sut->isWorkDay($friday));
        self::assertTrue($sut->isWorkDay($saturday));
        self::assertTrue($sut->isWorkDay($sunday));

        self::assertEquals(0, $sut->getWorkHoursForDay($monday));
        self::assertEquals(3600, $sut->getWorkHoursForDay($tuesday));
        self::assertEquals(0, $sut->getWorkHoursForDay($wednesday));
        self::assertEquals(7200, $sut->getWorkHoursForDay($thursday));
        self::assertEquals(0, $sut->getWorkHoursForDay($friday));
        self::assertEquals(1800, $sut->getWorkHoursForDay($saturday));
        self::assertEquals(9000, $sut->getWorkHoursForDay($sunday));
    }
}
