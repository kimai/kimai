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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RateResetCalculator::class)]
class RateResetCalculatorTest extends TestCase
{
    /**
     * Tests that rates are reset when project/activity/user changes
     * and no manual rate (fixedRate or hourlyRate) is set.
     */
    public function testWithResetWhenNoManualRatesSet(): void
    {
        $record = new Timesheet();
        $record->setRate(999.99);
        $record->setInternalRate(98.76);
        $record->setBillableMode(Timesheet::BILLABLE_NO);
        // Note: NOT setting fixedRate or hourlyRate - rates should be reset

        $user = new User();
        $user->setPreferences([
            new UserPreference(UserPreference::HOURLY_RATE, 75),
            new UserPreference(UserPreference::INTERNAL_RATE, 25)
        ]);
        $record->setUser($user);

        self::assertEquals(999.99, $record->getRate());
        self::assertNull($record->getHourlyRate());
        self::assertNull($record->getFixedRate());
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

    /**
     * Tests that rates are preserved when project/activity/user changes
     * but the record already has a fixedRate set (GitHub issue #5735).
     */
    public function testPreserveRatesWhenFixedRateIsSet(): void
    {
        $record = new Timesheet();
        $record->setRate(80.00);
        $record->setFixedRate(80.00);
        $record->setInternalRate(50.00);
        $record->setBillableMode(Timesheet::BILLABLE_YES);

        $user = new User();
        $record->setUser($user);

        self::assertEquals(80.00, $record->getRate());
        self::assertEquals(80.00, $record->getFixedRate());

        $sut = new RateResetCalculator();
        // Change project - rates should be preserved because fixedRate is set
        $sut->calculate($record, ['project' => [0 => new Project(), 1 => new Project()]]);

        // Rates should NOT be reset - fixedRate should be preserved
        self::assertEquals(80.00, $record->getRate());
        self::assertEquals(80.00, $record->getFixedRate());
        self::assertEquals(50.00, $record->getInternalRate());
        self::assertEquals(Timesheet::BILLABLE_YES, $record->getBillableMode());
    }

    /**
     * Tests that rates are preserved when project/activity/user changes
     * but the record already has an hourlyRate set.
     */
    public function testPreserveRatesWhenHourlyRateIsSet(): void
    {
        $record = new Timesheet();
        $record->setRate(100.00);
        $record->setHourlyRate(100.00);
        $record->setInternalRate(75.00);
        $record->setBillableMode(Timesheet::BILLABLE_YES);

        $user = new User();
        $record->setUser($user);

        self::assertEquals(100.00, $record->getRate());
        self::assertEquals(100.00, $record->getHourlyRate());

        $sut = new RateResetCalculator();
        // Change activity - rates should be preserved because hourlyRate is set
        $sut->calculate($record, ['activity' => [0 => null, 1 => null]]);

        // Rates should NOT be reset - hourlyRate should be preserved
        self::assertEquals(100.00, $record->getRate());
        self::assertEquals(100.00, $record->getHourlyRate());
        self::assertEquals(75.00, $record->getInternalRate());
        self::assertEquals(Timesheet::BILLABLE_YES, $record->getBillableMode());
    }

    /**
     * Tests that rates are reset when rate field is in changeset
     * (user explicitly modified it).
     */
    public function testNoResetWhenRateInChangeset(): void
    {
        $record = new Timesheet();
        $record->setRate(999.99);
        $record->setBillableMode(Timesheet::BILLABLE_NO);

        $sut = new RateResetCalculator();
        // Rate is in changeset - should return early, no reset
        $sut->calculate($record, ['rate' => [0 => 50.00, 1 => 999.99]]);

        // Rates should NOT be reset
        self::assertEquals(999.99, $record->getRate());
        self::assertEquals(Timesheet::BILLABLE_NO, $record->getBillableMode());
    }
}
