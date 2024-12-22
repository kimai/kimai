<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Activity;
use App\Entity\Project;
use App\Repository\Loader\ActivityLoader;

/**
 * @covers \App\Repository\Loader\ActivityLoader
 */
class ActivityLoaderTest extends AbstractLoaderTestCase
{
    public function testLoadResults(): void
    {
        $project = $this->createMock(Project::class);
        $project->expects($this->once())->method('getId')->willReturn(13);

        $entity = $this->createMock(Activity::class);
        $entity->expects($this->once())->method('getId')->willReturn(1);
        $entity->expects($this->exactly(3))->method('getProject')->willReturn($project);

        $em = $this->getEntityManagerMock(3);
        $sut = new ActivityLoader($em);

        $sut->loadResults([$entity]);
    }
}
