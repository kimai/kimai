<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Customer;
use App\Event\AbstractCustomerEvent;
use App\Event\CustomerStatisticEvent;
use App\Model\CustomerStatistic;

/**
 * @covers \App\Event\AbstractCustomerEvent
 * @covers \App\Event\CustomerStatisticEvent
 */
class CustomerStatisticEventTest extends AbstractCustomerEventTestCase
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerStatisticEvent($customer, new CustomerStatistic());
    }

    public function testStatistic(): void
    {
        $customer = new Customer('foo');
        $statistic = new CustomerStatistic();
        $sut = new CustomerStatisticEvent($customer, $statistic);

        self::assertSame($statistic, $sut->getStatistic());
        self::assertSame($customer, $sut->getCustomer());
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());

        $begin = new \DateTime('2020-08-08 12:34:56');
        $end = new \DateTime('2020-09-08 12:34:56');
        $sut = new CustomerStatisticEvent($customer, $statistic, $begin, $end);
        self::assertSame($begin, $sut->getBegin());
        self::assertSame($end, $sut->getEnd());
    }
}
