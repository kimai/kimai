<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\TrackingMode;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Tests\Configuration\TestConfigLoader;
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

    protected function createSut($default = '13:45:37')
    {
        $loader = new TestConfigLoader([]);
        $configuration = new SystemConfiguration($loader, ['timesheet' => ['default_begin' => $default]]);

        return new DurationOnlyMode($configuration);
    }

    public function testNow()
    {
        $seconds = (new \DateTime())->getTimestamp();
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime('18:50:32'));
        $mode = $this->createSut('now');
        $mode->create($timesheet);
        $diff = $timesheet->getBegin()->getTimestamp() - $seconds;
        // amount of seconds doesn't really matter, it must only be near "now"
        self::assertLessThanOrEqual(2, $diff);
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
