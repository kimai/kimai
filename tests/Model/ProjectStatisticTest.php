<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\ProjectStatistic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\ProjectStatistic
 */
class ProjectStatisticTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new ProjectStatistic();
        $this->assertEquals(0, $sut->getActivityAmount());
        $this->assertEquals(0, $sut->getRecordAmount());
        $this->assertEquals(0, $sut->getRecordDuration());
    }

    public function testSetter()
    {
        $sut = new ProjectStatistic();
        $sut->setRecordAmount(7654.298);
        $sut->setRecordDuration(826.10);
        $sut->setActivityAmount(13);

        $this->assertEquals(13, $sut->getActivityAmount());
        $this->assertEquals(7654, $sut->getRecordAmount());
        $this->assertEquals(826, $sut->getRecordDuration());
    }
}
