<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\User;
use App\Model\Statistic\Month;
use App\Model\UserStatistic;

/**
 * @covers \App\Model\UserStatistic
 */
class UserStatisticTest extends AbstractTimesheetCountedStatisticTest
{
    private function getSut(): UserStatistic
    {
        $user = new User();

        return new UserStatistic($user);
    }

    public function testDefaultValues(): void
    {
        $this->assertDefaultValues($this->getSut());
    }

    public function testSetter(): void
    {
        $this->assertSetter($this->getSut());
    }

    public function testJsonSerialize(): void
    {
        $this->assertJsonSerialize($this->getSut());
    }

    public function testAdditionalValues(): void
    {
        $user = new User();
        $sut = new UserStatistic($user);

        $this->assertSetter($sut);

        self::assertSame(22, $sut->getDuration());
        self::assertSame(1234, $sut->getDurationBillable());
        self::assertSame(323.97, $sut->getRate());
        self::assertSame(123.456, $sut->getRateBillable());
        self::assertSame(567.09, $sut->getInternalRate());

        $month = new Month('10');

        $sut->addValuesFromMonth($month);

        self::assertSame(22, $sut->getDuration());
        self::assertSame(1234, $sut->getDurationBillable());
        self::assertSame(323.97, $sut->getRate());
        self::assertSame(123.456, $sut->getRateBillable());
        self::assertSame(567.09, $sut->getInternalRate());

        $month = new Month('10');
        $month->setTotalDuration(123);
        $month->setBillableDuration(234);
        $month->setTotalRate(345.67);
        $month->setBillableRate(456.78);
        $month->setTotalInternalRate(567.89);

        $sut->addValuesFromMonth($month);

        self::assertSame(145, $sut->getDuration());
        self::assertSame(1468, $sut->getDurationBillable());
        self::assertSame('669.64', number_format($sut->getRate(), 2));
        self::assertSame(580.236, $sut->getRateBillable());
        self::assertSame(1134.98, $sut->getInternalRate());
    }
}
