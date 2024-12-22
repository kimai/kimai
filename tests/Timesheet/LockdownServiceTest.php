<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Configuration\ConfigLoaderInterface;
use App\Entity\Timesheet;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Timesheet\LockdownService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\LockdownService
 */
class LockdownServiceTest extends TestCase
{
    protected function createService(?string $start, ?string $end, ?string $grace = null, ?string $timezone = null): LockdownService
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, [
            'timesheet' => [
                'rules' => [
                    'lockdown_period_start' => $start,
                    'lockdown_period_end' => $end,
                    'lockdown_period_timezone' => $timezone,
                    'lockdown_grace_period' => $grace,
                ],
            ]
        ]);

        return new LockdownService($config);
    }

    public function testValidatorWithoutNowConstraint(): void
    {
        $sut = $this->createService('first day of last month', 'last day of last month', '+10 days');

        $begin = new \DateTime('first day of last month');
        $begin->modify('-5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        self::assertFalse($sut->isEditable($timesheet, new \DateTime(), false));
    }

    public function testValidatorWithEmptyTimesheet(): void
    {
        $sut = $this->createService('first day of last month', 'last day of last month', '+10 days');

        self::assertTrue($sut->isEditable(new Timesheet(), new \DateTime(), false));
    }

    public function testValidatorWithoutNowStringConstraint(): void
    {
        $sut = $this->createService('first day of last month', 'last day of last month', '+10 days');

        $begin = new \DateTime('first day of last month');
        $begin->modify('+5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        self::assertTrue($sut->isEditable($timesheet, new \DateTime('first day of this month'), false));
    }

    public function testValidatorWithEndBeforeStartPeriod(): void
    {
        $sut = $this->createService('first day of this month', 'last day of last month', '+10 days');

        $begin = new \DateTime('first day of last month');
        $begin->modify('+5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        self::assertTrue($sut->isEditable($timesheet, new \DateTime('first day of this month'), false));
    }

    /**
     * @dataProvider getTestData
     */
    public function testLockdown(bool $allowOverwriteGrace, string $beginModifier, string $nowModifier, bool $isViolation): void
    {
        $sut = $this->createService('first day of last month', 'last day of last month', '+10 days');

        $begin = new \DateTime('first day of last month');
        $begin->modify($beginModifier);
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $now = new \DateTime('first day of this month');
        $now->modify($nowModifier);

        $result = $sut->isEditable($timesheet, $now, $allowOverwriteGrace);
        if ($isViolation) {
            self::assertFalse($result);
        } else {
            self::assertTrue($result);
        }
    }

    public static function getTestData()
    {
        // changing before last dockdown period is not allowed
        yield [false, '-5 days', '+5 days', true];
        // changing before last dockdown period is not allowed with grace permission
        yield [true, '-5 days', '+5 days', true];
        // changing a value in the last lockdown period is allowed during grace period
        yield [false, '+5 days', '+5 days', false];
        // changing outside grace period is not allowed
        yield [false, '+5 days', '+11 days', true];
        // changing outside grace period is allowed with grace and full permission
        yield [true, '+5 days', '+11 days', false];
    }

    /**
     * @dataProvider getConfigTestData
     */
    public function testLockdownConfig(bool $allowOverwriteGrace, ?string $lockdownBegin, ?string $lockdownEnd, ?string $grace, bool $isViolation): void
    {
        $sut = $this->createService($lockdownBegin, $lockdownEnd, $grace);

        $begin = new \DateTime('first day of last month');
        $begin->modify('+5 days');
        $timesheet = new Timesheet();
        $timesheet->setBegin($begin);

        $now = new \DateTime('first day of this month');

        $result = $sut->isEditable($timesheet, $now, $allowOverwriteGrace);

        if ($isViolation) {
            self::assertFalse($result);
        } else {
            self::assertTrue($result);
        }
    }

    public static function getConfigTestData()
    {
        yield [false, null, null, null, false];
        yield [false, '+5 days', null, null, false];
        yield [false, null, '+5 days', null, false];

        yield [true, 'öööö', '+11 days', null, false];
        yield [true, '+5 days', '+5 of !!!!', null, false];
    }

    public function testIsLockdownActive(): void
    {
        $sut = $this->createService(null, null);
        self::assertFalse($sut->isLockdownActive());

        $sut = $this->createService('+5 days', null);
        self::assertFalse($sut->isLockdownActive());

        $sut = $this->createService(null, '+5 days');
        self::assertFalse($sut->isLockdownActive());

        $sut = $this->createService('+5 days', '+5 days');
        self::assertTrue($sut->isLockdownActive());
    }
}
