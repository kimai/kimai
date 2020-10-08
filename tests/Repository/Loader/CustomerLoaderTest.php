<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Customer;
use App\Repository\Loader\CustomerLoader;

/**
 * @covers \App\Repository\Loader\CustomerLoader
 * @covers \App\Repository\Loader\CustomerIdLoader
 */
class CustomerLoaderTest extends AbstractLoaderTest
{
    public function testLoadResults()
    {
        $em = $this->getEntityManagerMock(2);

        $sut = new CustomerLoader($em);

        $entity = $this->createMock(Customer::class);
        $entity->expects($this->once())->method('getId')->willReturn(1);

        $sut->loadResults([$entity]);
    }
}
