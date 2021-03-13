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
        self::assertEquals(0, $sut->getActivityAmount());
        self::assertEquals(0, $sut->getRecordAmount());
        self::assertEquals(0, $sut->getRecordDuration());
    }

    public function testSetter()
    {
        $sut = new ProjectStatistic();
        $sut->setRecordAmount(7654);
        $sut->setRecordDuration(826);
        $sut->setActivityAmount(13);

        self::assertEquals(13, $sut->getActivityAmount());
        self::assertEquals(7654, $sut->getRecordAmount());
        self::assertEquals(826, $sut->getRecordDuration());
    }
}
