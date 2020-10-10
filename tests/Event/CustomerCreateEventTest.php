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
use App\Event\CustomerCreateEvent;

/**
 * @covers \App\Event\AbstractCustomerEvent
 * @covers \App\Event\CustomerCreateEvent
 */
class CustomerCreateEventTest extends AbstractCustomerEventTest
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerCreateEvent($customer);
    }
}
