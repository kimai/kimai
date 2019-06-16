<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\TrackingMode;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Timesheet\TrackingMode\DurationFixedBeginMode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Timesheet\TrackingMode\DurationFixedBeginMode
 */
class DurationFixedBeginModeTest extends TestCase
{
    protected function createSut()
    {
        $loader = new TestConfigLoader([]);
        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $configuration = new TimesheetConfiguration($loader, ['default_begin' => '13:47']);

        return new DurationFixedBeginMode($dateTime, $configuration);
    }

    public function testDefaultValues()
    {
        $sut = $this->createSut();

        self::assertFalse($sut->canEditBegin());
        self::assertFalse($sut->canEditEnd());
        self::assertTrue($sut->canEditDuration());
        self::assertFalse($sut->canUpdateTimesWithAPI());
        self::assertFalse($sut->canSeeBeginAndEndTimes());
        self::assertEquals('duration_fixed_begin', $sut->getId());
    }

    public function testCreate()
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime('22:54'));
        $request = new Request();

        $sut = $this->createSut();
        self::assertEquals('22:54', $timesheet->getBegin()->format('H:i'));
        $sut->create($timesheet, $request);
        self::assertEquals('13:47', $timesheet->getBegin()->format('H:i'));
    }
}
