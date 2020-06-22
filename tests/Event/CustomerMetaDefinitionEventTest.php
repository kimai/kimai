<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Customer;
use App\Event\CustomerMetaDefinitionEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\CustomerMetaDefinitionEvent
 */
class CustomerMetaDefinitionEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $customer = new Customer();
        $sut = new CustomerMetaDefinitionEvent($customer);
        $this->assertSame($customer, $sut->getEntity());
    }
}
