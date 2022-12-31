<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\Calculator;

use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Timesheet\Calculator\RateResetCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\Calculator\RateResetCalculator
 */
class RateResetCalculatorTest extends TestCase
{
    public function testWithReset(): void
    {
        $record = new Timesheet();
        $record->setRate(999.99);
        $record->setHourlyRate(100);
        $record->setFixedRate(123.45);
        $record->setInternalRate(98.76);
        $record->setBillableMode(Timesheet::BILLABLE_NO);

        $user = new User();
        $user->setPreferences([
            new UserPreference(UserPreference::HOURLY_RATE, 75),
            new UserPreference(UserPreference::INTERNAL_RATE, 25)
        ]);
        $record->setUser($user);

        self::assertEquals(999.99, $record->getRate());
        self::assertEquals(100, $record->getHourlyRate());
        self::assertEquals(123.45, $record->getFixedRate());
        self::assertEquals(98.76, $record->getInternalRate());
        self::assertEquals(Timesheet::BILLABLE_NO, $record->getBillableMode());

        $sut = new RateResetCalculator();
        // 0 = before, 1 = after
        $sut->calculate($record, ['project' => [0 => new Project(), 1 => new Project()]]);

        self::assertEquals(0.00, $record->getRate());
        self::assertNull($record->getHourlyRate());
        self::assertNull($record->getFixedRate());
        self::assertNull($record->getInternalRate());
        self::assertEquals(Timesheet::BILLABLE_AUTOMATIC, $record->getBillableMode());
    }
}
