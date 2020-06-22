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
use App\Tests\Mocks\RoundingServiceFactory;
use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Timesheet\TrackingMode\DefaultMode;

/**
 * @covers \App\Timesheet\TrackingMode\DefaultMode
 */
class DefaultModeTest extends AbstractTrackingModeTest
{
    protected function assertDefaultBegin(Timesheet $timesheet)
    {
        self::assertNotNull($timesheet->getBegin());
        self::assertInstanceOf(\DateTime::class, $timesheet->getBegin());
    }

    /**
     * @return DefaultMode
     */
    protected function createSut()
    {
        $loader = new TestConfigLoader([]);
        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $configuration = new TimesheetConfiguration($loader, ['default_begin' => '13:47']);

        return new DefaultMode($dateTime, $configuration, (new RoundingServiceFactory($this))->create());
    }

    public function testDefaultValues()
    {
        $sut = $this->createSut();

        self::assertTrue($sut->canEditBegin());
        self::assertTrue($sut->canEditEnd());
        self::assertFalse($sut->canEditDuration());
        self::assertTrue($sut->canUpdateTimesWithAPI());
        self::assertTrue($sut->canSeeBeginAndEndTimes());
        self::assertEquals('default', $sut->getId());
    }
}
