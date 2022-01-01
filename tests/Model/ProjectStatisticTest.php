<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\CustomerStatistic;
use App\Model\ProjectStatistic;

/**
 * @covers \App\Model\ProjectStatistic
 */
class ProjectStatisticTest extends AbstractTimesheetCountedStatisticTest
{
    public function testDefaultValues()
    {
        $this->assertDefaultValues(new CustomerStatistic());
    }

    public function testSetter()
    {
        $this->assertSetter(new CustomerStatistic());
    }

    public function testJsonSerialize()
    {
        $this->assertJsonSerialize(new CustomerStatistic());
    }

    /**
     * @group legacy
     */
    public function testAdditionalSetter()
    {
        $sut = new ProjectStatistic();

        self::assertEquals(0, $sut->getActivityAmount());
        $sut->setActivityAmount(13);
        self::assertEquals(13, $sut->getActivityAmount());
    }
}
