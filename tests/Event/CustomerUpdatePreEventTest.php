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
use App\Event\CustomerUpdatePreEvent;

/**
 * @covers \App\Event\AbstractCustomerEvent
 * @covers \App\Event\CustomerUpdatePreEvent
 */
class CustomerUpdatePreEventTest extends AbstractCustomerEventTest
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerUpdatePreEvent($customer);
    }
}
