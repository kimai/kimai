<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Project;
use App\Event\AbstractProjectEvent;
use App\Event\ProjectStatisticEvent;
use App\Model\ProjectStatistic;

/**
 * @covers \App\Event\AbstractProjectEvent
 * @covers \App\Event\ProjectStatisticEvent
 */
class ProjectStatisticEventTest extends AbstractProjectEventTest
{
    protected function createProjectEvent(Project $project): AbstractProjectEvent
    {
        return new ProjectStatisticEvent($project, new ProjectStatistic());
    }

    public function testStatistic()
    {
        $project = new Project();
        $statistic = new ProjectStatistic();
        $sut = new ProjectStatisticEvent($project, $statistic);

        self::assertSame($statistic, $sut->getStatistic());
    }
}
