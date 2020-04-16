<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Activity;
use App\Repository\Loader\ActivityLoader;

/**
 * @covers \App\Repository\Loader\ActivityLoader
 * @covers \App\Repository\Loader\ActivityIdLoader
 */
class ActivityLoaderTest extends AbstractLoaderTest
{
    public function testLoadResults()
    {
        // mock needs improvements, because it should be 5
        $em = $this->getEntityManagerMock(2);

        $sut = new ActivityLoader($em);

        $entity = $this->createMock(Activity::class);
        $entity->expects($this->once())->method('getId')->willReturn(1);

        $sut->loadResults([$entity]);
    }
}
