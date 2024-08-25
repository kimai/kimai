<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Customer;
use App\Entity\Team;
use App\Repository\Loader\CustomerLoader;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\CustomerQueryHydrate;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @covers \App\Repository\Loader\CustomerLoader
 */
class CustomerLoaderTest extends AbstractLoaderTest
{
    public function testLoadResults(): void
    {
        $em = $this->getEntityManagerMock(2);

        $query = new CustomerQuery();
        $query->addHydrate(CustomerQueryHydrate::TEAM_MEMBER);

        $sut = new CustomerLoader($em, $query);

        $team = $this->createMock(Team::class);
        $team->expects($this->once())->method('getId')->willReturn(1);
        $entity = $this->createMock(Customer::class);
        $entity->expects($this->once())->method('getTeams')->willReturn(new ArrayCollection([$team]));
        $entity->expects($this->once())->method('getId')->willReturn(1);

        $sut->loadResults([$entity]);
    }
}
