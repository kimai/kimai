<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\Calculator;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Timesheet\Calculator\RateResetCalculator;
use App\Timesheet\Rate;
use App\Timesheet\RateServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RateResetCalculator::class)]
class RateResetCalculatorTest extends TestCase
{
    public function testAutomaticRatesAreReset(): void
    {
        $record = new Timesheet();
        $record->setRate(999.99);
        $record->setHourlyRate(100);
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
        self::assertNull($record->getFixedRate());
        self::assertEquals(98.76, $record->getInternalRate());
        self::assertEquals(Timesheet::BILLABLE_NO, $record->getBillableMode());

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->once())
            ->method('calculate')
            ->willReturn(new Rate(100, 25, 100));
        $sut = new RateResetCalculator($rateService);
        // 0 = before, 1 = after
        $sut->calculate($record, ['project' => [0 => new Project(), 1 => new Project()]]);

        self::assertEquals(0.00, $record->getRate());
        self::assertNull($record->getHourlyRate());
        self::assertNull($record->getFixedRate());
        self::assertNull($record->getInternalRate());
        self::assertEquals(Timesheet::BILLABLE_AUTOMATIC, $record->getBillableMode());
    }

    public function testManualFixedRateIsPreserved(): void
    {
        $record = $this->createTimesheet();
        $record->setFixedRate(80);
        $record->setRate(80);

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->once())
            ->method('calculate')
            ->willReturn(new Rate(50, 25, 50));

        $sut = new RateResetCalculator($rateService);
        $sut->calculate($record, ['activity' => [new Activity(), new Activity()]]);

        self::assertSame(80.0, $record->getFixedRate());
        self::assertNull($record->getHourlyRate());
        self::assertSame(0.0, $record->getRate());
    }

    public function testAutomaticFixedRateIsReset(): void
    {
        $record = $this->createTimesheet();
        $record->setFixedRate(80);
        $record->setRate(80);

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->once())
            ->method('calculate')
            ->willReturn(new Rate(80, 25, null, 80));

        $sut = new RateResetCalculator($rateService);
        $sut->calculate($record, ['activity' => [new Activity(), new Activity()]]);

        self::assertNull($record->getFixedRate());
        self::assertNull($record->getHourlyRate());
        self::assertSame(0.0, $record->getRate());
    }

    public function testAutomaticFixedRateWithFloatingPointDifferenceIsReset(): void
    {
        $record = $this->createTimesheet();
        $record->setFixedRate(80.0000001);
        $record->setRate(80.0000001);

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->once())
            ->method('calculate')
            ->willReturn(new Rate(80, 25, null, 80));

        $sut = new RateResetCalculator($rateService);
        $sut->calculate($record, ['activity' => [new Activity(), new Activity()]]);

        self::assertNull($record->getFixedRate());
        self::assertNull($record->getHourlyRate());
        self::assertSame(0.0, $record->getRate());
    }

    public function testManualHourlyRateIsPreserved(): void
    {
        $record = $this->createTimesheet();
        $record->setHourlyRate(80);
        $record->setRate(80);

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->once())
            ->method('calculate')
            ->willReturn(new Rate(50, 25, 50));

        $sut = new RateResetCalculator($rateService);
        $sut->calculate($record, ['project' => [new Project(), new Project()]]);

        self::assertNull($record->getFixedRate());
        self::assertSame(80.0, $record->getHourlyRate());
        self::assertSame(0.0, $record->getRate());
    }

    public function testAutomaticHourlyRateIsReset(): void
    {
        $record = $this->createTimesheet();
        $record->setHourlyRate(50);
        $record->setRate(50);

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->once())
            ->method('calculate')
            ->willReturn(new Rate(50, 25, 50));

        $sut = new RateResetCalculator($rateService);
        $sut->calculate($record, ['activity' => [new Activity(), new Activity()]]);

        self::assertNull($record->getFixedRate());
        self::assertNull($record->getHourlyRate());
        self::assertSame(0.0, $record->getRate());
    }

    public function testAutomaticHourlyRateWithFloatingPointDifferenceIsReset(): void
    {
        $record = $this->createTimesheet();
        $record->setHourlyRate(50.0000001);
        $record->setRate(50.0000001);

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->once())
            ->method('calculate')
            ->willReturn(new Rate(50, 25, 50));

        $sut = new RateResetCalculator($rateService);
        $sut->calculate($record, ['activity' => [new Activity(), new Activity()]]);

        self::assertNull($record->getFixedRate());
        self::assertNull($record->getHourlyRate());
        self::assertSame(0.0, $record->getRate());
    }

    public function testManualRateChangeSkipsReset(): void
    {
        $record = $this->createTimesheet();
        $record->setFixedRate(81);
        $record->setRate(81);

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->never())->method('calculate');

        $sut = new RateResetCalculator($rateService);
        $sut->calculate($record, [
            'activity' => [new Activity(), new Activity()],
            'fixedRate' => [80, 81],
        ]);

        self::assertSame(81.0, $record->getFixedRate());
        self::assertSame(81.0, $record->getRate());
    }

    public function testUnrelatedChangeSkipsReset(): void
    {
        $record = $this->createTimesheet();
        $record->setFixedRate(80);
        $record->setRate(80);

        $rateService = $this->createMock(RateServiceInterface::class);
        $rateService->expects($this->never())->method('calculate');

        $sut = new RateResetCalculator($rateService);
        $sut->calculate($record, ['description' => ['old', 'new']]);

        self::assertSame(80.0, $record->getFixedRate());
        self::assertSame(80.0, $record->getRate());
    }

    private function createTimesheet(): Timesheet
    {
        $record = new Timesheet();
        $record->setUser(new User());

        return $record;
    }
}
