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
use App\Event\CustomerDetailControllerEvent;

/**
 * @covers \App\Event\AbstractCustomerEvent
 * @covers \App\Event\CustomerDetailControllerEvent
 */
class CustomerDetailControllerEventTest extends AbstractCustomerEventTest
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerDetailControllerEvent($customer);
    }

    public function testController(): void
    {
        /** @var CustomerDetailControllerEvent $event */
        $event = $this->createCustomerEvent(new Customer('foo'));
        $event->addController('Foo\\Bar::helloWorld');
        $this->assertEquals(['Foo\\Bar::helloWorld'], $event->getController());
    }
}
