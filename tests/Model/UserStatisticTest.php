<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\User;
use App\Model\ActivityStatistic;
use App\Model\UserStatistic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\UserStatistic
 */
class UserStatisticTest extends TestCase
{
    public function testDefaultValues()
    {
        $user = new User();
        $sut = new UserStatistic($user);
        $this->assertEquals(0, $sut->getRecordRate());
    }

    public function testSetter()
    {
        $sut = new ActivityStatistic();
        $sut->setRecordAmount(7654);
        $sut->setRecordDuration(826);

        $this->assertEquals(7654, $sut->getRecordAmount());
        $this->assertEquals(826, $sut->getRecordDuration());
    }
}
