<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use App\Entity\Customer;
use App\Event\AbstractCustomerEvent;
use App\Event\CustomerCreateEvent;

#[CoversClass(AbstractCustomerEvent::class)]
#[CoversClass(CustomerCreateEvent::class)]
class CustomerCreateEventTest extends AbstractCustomerEventTestCase
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerCreateEvent($customer);
    }
}
