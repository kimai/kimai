<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Customer;
use App\Entity\Project;
use App\Repository\Loader\ProjectLoader;

/**
 * @covers \App\Repository\Loader\ProjectLoader
 */
class ProjectLoaderTest extends AbstractLoaderTestCase
{
    public function testLoadResults(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->expects($this->once())->method('getId')->willReturn(13);

        $entity = $this->createMock(Project::class);
        $entity->expects($this->once())->method('getId')->willReturn(1);
        $entity->expects($this->exactly(2))->method('getCustomer')->willReturn($customer);

        $results = [$entity];

        $em = $this->getEntityManagerMock(2, $results);

        $sut = new ProjectLoader($em);
        $sut->loadResults([$entity]);
    }
}
