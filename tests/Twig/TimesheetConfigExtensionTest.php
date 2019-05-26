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
use App\Twig\TimesheetConfigExtension;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Twig\TimesheetConfigExtension
 */
class TimesheetConfigExtensionTest extends TestCase
{
    public function testGetFunctions()
    {
        $loader = $this->getMockBuilder(ConfigLoaderInterface::class)->getMock();
        $config = new TimesheetConfiguration($loader, ['mode' => 'duration_only']);
        $sut = new TimesheetConfigExtension($config);
        $filters = $sut->getFunctions();
        $this->assertCount(2, $filters);
        $this->assertEquals('is_duration_only', $filters[0]->getName());
        $this->assertEquals('is_punch_mode', $filters[1]->getName());
    }

    public function testIsDurationOnly()
    {
        $loader = $this->getMockBuilder(ConfigLoaderInterface::class)->getMock();
        $config = new TimesheetConfiguration($loader, ['mode' => 'duration_only']);
        $sut = new TimesheetConfigExtension($config);
        $this->assertTrue($sut->isDurationOnly());
        $this->assertFalse($sut->isPunchInOut());
    }

    public function testIsNotDurationOnly()
    {
        $loader = $this->getMockBuilder(ConfigLoaderInterface::class)->getMock();
        $config = new TimesheetConfiguration($loader, ['mode' => 'default']);
        $sut = new TimesheetConfigExtension($config);
        $this->assertFalse($sut->isDurationOnly());
        $this->assertFalse($sut->isPunchInOut());
    }

    public function testIsPunchInOut()
    {
        $loader = $this->getMockBuilder(ConfigLoaderInterface::class)->getMock();
        $config = new TimesheetConfiguration($loader, ['mode' => 'punch']);
        $sut = new TimesheetConfigExtension($config);
        $this->assertFalse($sut->isDurationOnly());
        $this->assertTrue($sut->isPunchInOut());
    }
}
