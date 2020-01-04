<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Model;

use App\API\Model\TimesheetConfig;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\API\Model\TimesheetConfig
 */
class TimesheetConfigTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new TimesheetConfig();
        $this->assertTrue($sut->isAllowFutureTimes());
        $this->assertEquals('now', $sut->getDefaultBeginTime());
        $this->assertEquals('default', $sut->getTrackingMode());
        $this->assertEquals(1, $sut->getActiveEntriesSoftLimit());
        $this->assertEquals(1, $sut->getActiveEntriesHardLimit());
    }

    public function testSetter()
    {
        $sut = new TimesheetConfig();

        $this->assertInstanceOf(TimesheetConfig::class, $sut->setIsAllowFutureTimes(false));
        $this->assertInstanceOf(TimesheetConfig::class, $sut->setDefaultBeginTime('08:00'));
        $this->assertInstanceOf(TimesheetConfig::class, $sut->setTrackingMode('punch'));
        $this->assertInstanceOf(TimesheetConfig::class, $sut->setActiveEntriesSoftLimit(2));
        $this->assertInstanceOf(TimesheetConfig::class, $sut->setActiveEntriesHardLimit(3));

        $this->assertFalse($sut->isAllowFutureTimes());
        $this->assertEquals('08:00', $sut->getDefaultBeginTime());
        $this->assertEquals('punch', $sut->getTrackingMode());
        $this->assertEquals(2, $sut->getActiveEntriesSoftLimit());
        $this->assertEquals(3, $sut->getActiveEntriesHardLimit());
    }
}
