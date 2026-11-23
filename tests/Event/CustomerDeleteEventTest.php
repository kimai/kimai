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
use App\Event\CustomerDeleteEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CustomerDeleteEvent::class)]
class CustomerDeleteEventTest extends AbstractCustomerEventTestCase
{
    protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent
    {
        return new CustomerDeleteEvent($customer);
    }

    public function testReplacement(): void
    {
        $entity = new Customer('customer 1');
        $replacement = new Customer('customer 2');

        $sut = new CustomerDeleteEvent($entity, $replacement);

        self::assertSame($entity, $sut->getCustomer());
        self::assertSame($replacement, $sut->getReplacementCustomer());
    }
}
