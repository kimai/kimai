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
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractCustomerEventTestCase extends TestCase
{
    abstract protected function createCustomerEvent(Customer $customer): AbstractCustomerEvent;

    public function testGetterAndSetter(): void
    {
        $customer = new Customer('foo');
        $sut = $this->createCustomerEvent($customer);

        self::assertInstanceOf(Event::class, $sut);
        self::assertSame($customer, $sut->getCustomer());
    }
}
