<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\TimesheetConfiguration;
use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Timesheet\TrackingModeService;
use App\Twig\TimesheetConfigExtension;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Twig\TimesheetConfigExtension
 */
class TimesheetConfigExtensionTest extends TestCase
{
    protected function createSut(TimesheetConfiguration $config): TimesheetConfigExtension
    {
        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $service = new TrackingModeService($dateTime, $config);
        $sut = new TimesheetConfigExtension($service);

        return $sut;
    }

    public function testGetFunctions()
    {
        $loader = $this->getMockBuilder(ConfigLoaderInterface::class)->getMock();
        $config = new TimesheetConfiguration($loader, ['mode' => 'duration_only']);
        $sut = $this->createSut($config);
        $filters = $sut->getFunctions();
        $this->assertCount(1, $filters);
        $this->assertEquals('is_punch_mode', $filters[0]->getName());
    }

    public function testIsNotDurationOnly()
    {
        $loader = $this->getMockBuilder(ConfigLoaderInterface::class)->getMock();
        $config = new TimesheetConfiguration($loader, ['mode' => 'default']);
        $sut = $this->createSut($config);
        $this->assertFalse($sut->isPunchInOut());
    }

    public function testIsPunchInOut()
    {
        $loader = $this->getMockBuilder(ConfigLoaderInterface::class)->getMock();
        $config = new TimesheetConfiguration($loader, ['mode' => 'punch']);
        $sut = $this->createSut($config);
        $this->assertTrue($sut->isPunchInOut());
    }
}
