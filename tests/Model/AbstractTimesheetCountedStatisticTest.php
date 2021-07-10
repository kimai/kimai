<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\TimesheetCountedStatistic;
use PHPUnit\Framework\TestCase;

abstract class AbstractTimesheetCountedStatisticTest extends TestCase
{
    protected function assertDefaultValues(TimesheetCountedStatistic $sut)
    {
        self::assertSame(0.0, $sut->getRecordRate());
        self::assertSame(0.0, $sut->getRate());
        self::assertSame(0, $sut->getRecordDuration());
        self::assertSame(0, $sut->getDuration());
        self::assertSame(0, $sut->getRecordAmount());
        self::assertSame(0.0, $sut->getRecordInternalRate());
        self::assertSame(0, $sut->getValue());
        self::assertSame(0, $sut->getDurationBillable());
        self::assertSame(0.0, $sut->getRateBillable());
        self::assertSame(0, $sut->getRecordAmountBillable());
    }

    protected function assertSetter(TimesheetCountedStatistic $sut)
    {
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordRate(23.97));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordDuration(21));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordAmount(5));
        self::assertInstanceOf(TimesheetCountedStatistic::class, $sut->setRecordInternalRate(99.09));

        self::assertSame(23.97, $sut->getRecordRate());
        self::assertSame(23.97, $sut->getRate());
        self::assertSame(21, $sut->getRecordDuration());
        self::assertSame(21, $sut->getDuration());
        self::assertSame(21, $sut->getValue());
        self::assertSame(5, $sut->getRecordAmount());
        self::assertSame(99.09, $sut->getRecordInternalRate());

        $sut->setRateBillable(123.456);
        $sut->setDurationBillable(1234);
        $sut->setRecordAmountBillable(4321);

        self::assertSame(123.456, $sut->getRateBillable());
        self::assertSame(1234, $sut->getDurationBillable());
        self::assertSame(4321, $sut->getRecordAmountBillable());
    }

    protected function assertJsonSerialize(TimesheetCountedStatistic $sut)
    {
        self::assertInstanceOf(\JsonSerializable::class, $sut);
        $sut->setRecordRate(23.97);
        $sut->setRecordDuration(21);
        $sut->setRecordAmount(5);
        $sut->setRecordInternalRate(99.09);
        $sut->setRateBillable(123.456);
        $sut->setDurationBillable(1234);
        $sut->setRecordAmountBillable(4321);

        $json = $sut->jsonSerialize();
        foreach (['duration', 'duration_billable', 'rate', 'rate_billable', 'rate_internal', 'amount', 'amount_billable'] as $key) {
            self::assertArrayHasKey($key, $json);
        }

        self::assertSame(21, $json['duration']);
        self::assertSame(1234, $json['duration_billable']);
        self::assertSame(23.97, $json['rate']);
        self::assertSame(123.456, $json['rate_billable']);
        self::assertSame(99.09, $json['rate_internal']);
        self::assertSame(5, $json['amount']);
        self::assertSame(4321, $json['amount_billable']);
    }
}
