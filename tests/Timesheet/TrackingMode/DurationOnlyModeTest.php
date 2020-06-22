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
use App\Timesheet\TrackingMode\DurationOnlyMode;

/**
 * @covers \App\Timesheet\TrackingMode\DurationOnlyMode
 */
class DurationOnlyModeTest extends AbstractTrackingModeTest
{
    protected function assertDefaultBegin(Timesheet $timesheet)
    {
        self::assertNotNull($timesheet->getBegin());
        self::assertEquals('13:45:37', $timesheet->getBegin()->format('H:i:s'));
    }

    protected function createSut()
    {
        $loader = new TestConfigLoader([]);
        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $configuration = new TimesheetConfiguration($loader, ['default_begin' => '13:45:37']);

        return new DurationOnlyMode($dateTime, $configuration);
    }

    public function testDefaultValues()
    {
        $sut = $this->createSut();

        self::assertTrue($sut->canEditBegin());
        self::assertFalse($sut->canEditEnd());
        self::assertTrue($sut->canEditDuration());
        self::assertTrue($sut->canUpdateTimesWithAPI());
        self::assertFalse($sut->canSeeBeginAndEndTimes());
        self::assertEquals('duration_only', $sut->getId());
    }
}
