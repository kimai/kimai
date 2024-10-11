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
    public function testSetter(): void
    {
        $sut = new TimesheetConfig();
        $sut->setIsAllowFutureTimes(false);
        $sut->setIsAllowOverlapping(false);
        $sut->setDefaultBeginTime('08:00');
        $sut->setTrackingMode('punch');
        $sut->setActiveEntriesHardLimit(3);

        $obj = new \ReflectionClass($sut);

        self::assertFalse($obj->getProperty('isAllowFutureTimes')->getValue($sut));
        self::assertFalse($obj->getProperty('isAllowOverlapping')->getValue($sut));
        self::assertEquals('08:00', $obj->getProperty('defaultBeginTime')->getValue($sut));
        self::assertEquals('punch', $obj->getProperty('trackingMode')->getValue($sut));
        self::assertEquals(3, $obj->getProperty('activeEntriesHardLimit')->getValue($sut));
    }
}
