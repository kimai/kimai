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
use App\Repository\Query\CustomerQuery;

/**
 * @covers \App\Repository\Loader\CustomerLoader
 */
class CustomerLoaderTest extends AbstractLoaderTest
{
    public function testLoadResults(): void
    {
        $em = $this->getEntityManagerMock(1);

        $query = new CustomerQuery();
        $query->loadTeams();

        $sut = new CustomerLoader($em, $query);

        $entity = $this->createMock(Customer::class);

        $sut->loadResults([$entity]);
    }
}
