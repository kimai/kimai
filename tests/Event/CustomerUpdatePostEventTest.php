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
use App\Event\CustomerUpdatePostEvent;

/**
 * @covers \App\Event\AbstractCustomerEvent
 * @covers \App\Event\CustomerUpdatePostEvent
 */
class CustomerUpdatePostEventTest extends AbstractCustomerEventTest
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerUpdatePostEvent($customer);
    }
}
