<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Configuration\TimesheetConfiguration;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Timesheet\TrackingMode\PunchInOutMode;
use App\Timesheet\TrackingModeService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\TrackingModeService
 */
class TrackingModeServiceTest extends TestCase
{
    public function testDefaultTrackingModesAreRegistered()
    {
        $loader = new TestConfigLoader([]);
        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $configuration = new TimesheetConfiguration($loader, ['mode' => 'punch']);

        $sut = new TrackingModeService($dateTime, $configuration);

        $modes = $sut->getModes();
        self::assertGreaterThanOrEqual(4, $modes);

        $ids = [];
        foreach ($modes as $mode) {
            $ids[] = $mode->getId();
        }

        self::assertContains('default', $ids);
        self::assertContains('punch', $ids);
        self::assertContains('duration_only', $ids);
        self::assertContains('duration_fixed_begin', $ids);
    }

    public function testGetActiveMode()
    {
        $loader = new TestConfigLoader([]);
        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $configuration = new TimesheetConfiguration($loader, ['mode' => 'punch']);

        $sut = new TrackingModeService($dateTime, $configuration);

        self::assertInstanceOf(PunchInOutMode::class, $sut->getActiveMode());
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @expectedExceptionMessage You have requested a non-existent service "xxxxxx"
     */
    public function testGetActiveModeThrowsExceptionOnlyInvalidMode()
    {
        $loader = new TestConfigLoader([]);
        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $configuration = new TimesheetConfiguration($loader, ['mode' => 'xxxxxx']);

        $sut = new TrackingModeService($dateTime, $configuration);

        $sut->getActiveMode();
    }
}
