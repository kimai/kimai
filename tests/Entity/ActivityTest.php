<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Activity;

/**
 * @covers \App\Entity\Activity
 */
class ActivityTest extends AbstractEntityTest
{
    public function testDefaultValues()
    {
        $sut = new Activity();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getProject());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getComment());
        $this->assertTrue($sut->getVisible());
        // timesheets
        $this->assertNull($sut->getFixedRate());
        $this->assertNull($sut->getHourlyRate());

        $this->assertInstanceOf(Activity::class, $sut->setFixedRate(13.47));
        $this->assertEquals(13.47, $sut->getFixedRate());
        $this->assertInstanceOf(Activity::class, $sut->setHourlyRate(99));
        $this->assertEquals(99, $sut->getHourlyRate());
    }
}
