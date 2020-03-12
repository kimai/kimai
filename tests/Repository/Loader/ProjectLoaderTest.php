<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Project;
use App\Repository\Loader\ProjectLoader;

/**
 * @covers \App\Repository\Loader\ProjectLoader
 * @covers \App\Repository\Loader\ProjectIdLoader
 */
class ProjectLoaderTest extends AbstractLoaderTest
{
    public function testLoadResults()
    {
        $em = $this->getEntityManagerMock(4);

        $sut = new ProjectLoader($em);

        $entity = $this->createMock(Project::class);
        $entity->expects($this->once())->method('getId')->willReturn(1);

        $sut->loadResults([$entity]);
    }
}
