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
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractCustomerEvent::class)]
#[CoversClass(CustomerUpdatePreEvent::class)]
class CustomerUpdatePreEventTest extends AbstractCustomerEventTestCase
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerUpdatePreEvent($customer);
    }
}
