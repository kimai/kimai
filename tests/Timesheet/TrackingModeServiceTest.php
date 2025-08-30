<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Tests\Mocks\TrackingModeServiceFactory;
use App\Timesheet\TrackingMode\PunchInOutMode;
use App\Timesheet\TrackingModeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

#[CoversClass(TrackingModeService::class)]
class TrackingModeServiceTest extends TestCase
{
    public function testDefaultTrackingModesAreRegistered(): void
    {
        $sut = (new TrackingModeServiceFactory($this))->create('punch');

        $modes = $sut->getModes();
        self::assertGreaterThanOrEqual(4, $modes);

        $ids = [];
        foreach ($modes as $mode) {
            $ids[] = $mode->getId();
        }

        self::assertContains('default', $ids);
        self::assertContains('punch', $ids);
        self::assertContains('duration_fixed_begin', $ids);
    }

    public function testGetActiveMode(): void
    {
        $sut = (new TrackingModeServiceFactory($this))->create('punch');

        self::assertInstanceOf(PunchInOutMode::class, $sut->getActiveMode());
    }

    public function testGetActiveModeThrowsExceptionOnlyInvalidMode(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('You have requested a non-existent service "xxxxxx"');

        $sut = (new TrackingModeServiceFactory($this))->create('xxxxxx');

        $sut->getActiveMode();
    }
}
