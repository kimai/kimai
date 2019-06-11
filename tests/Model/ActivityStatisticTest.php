<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\ActivityStatistic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\ActivityStatistic
 */
class ActivityStatisticTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new ActivityStatistic();
        $this->assertEquals(0, $sut->getRecordAmount());
        $this->assertEquals(0, $sut->getRecordDuration());
    }

    public function testSetter()
    {
        $sut = new ActivityStatistic();
        $sut->setRecordAmount(7654.298);
        $sut->setRecordDuration(826.10);

        $this->assertEquals(7654, $sut->getRecordAmount());
        $this->assertEquals(826, $sut->getRecordDuration());
    }
}
