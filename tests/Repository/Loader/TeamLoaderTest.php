<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Team;
use App\Repository\Loader\TeamLoader;

/**
 * @covers \App\Repository\Loader\TeamLoader
 */
class TeamLoaderTest extends AbstractLoaderTestCase
{
    public function testLoadResults(): void
    {
        $em = $this->getEntityManagerMock(2);

        $sut = new TeamLoader($em);

        $entity = $this->createMock(Team::class);
        $entity->expects($this->once())->method('getId')->willReturn(1);

        $sut->loadResults([$entity]);
    }
}
