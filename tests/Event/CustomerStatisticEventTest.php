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
class CustomerStatisticEventTest extends AbstractCustomerEventTest
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerStatisticEvent($customer, new CustomerStatistic());
    }

    public function testStatistic()
    {
        $customer = new Customer();
        $statistic = new CustomerStatistic();
        $sut = new CustomerStatisticEvent($customer, $statistic);

        self::assertSame($statistic, $sut->getStatistic());
    }
}
