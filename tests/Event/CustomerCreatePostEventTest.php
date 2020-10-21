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
use App\Event\CustomerCreatePostEvent;

/**
 * @covers \App\Event\AbstractCustomerEvent
 * @covers \App\Event\CustomerCreatePostEvent
 */
class CustomerCreatePostEventTest extends AbstractCustomerEventTest
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerCreatePostEvent($customer);
    }
}
