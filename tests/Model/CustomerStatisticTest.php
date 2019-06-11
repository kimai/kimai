<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\CustomerStatistic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\CustomerStatistic
 */
class CustomerStatisticTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new CustomerStatistic();
        $this->assertEquals(0, $sut->getActivityAmount());
        $this->assertEquals(0, $sut->getProjectAmount());
        $this->assertEquals(0, $sut->getRecordAmount());
        $this->assertEquals(0, $sut->getRecordDuration());
    }

    public function testSetter()
    {
        $sut = new CustomerStatistic();
        $sut->setRecordAmount(7654.298);
        $sut->setRecordDuration(826.10);
        $sut->setActivityAmount(13);
        $sut->setProjectAmount(2);

        $this->assertEquals(13, $sut->getActivityAmount());
        $this->assertEquals(2, $sut->getProjectAmount());
        $this->assertEquals(7654, $sut->getRecordAmount());
        $this->assertEquals(826, $sut->getRecordDuration());
    }
}
