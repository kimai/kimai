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

    public function testStatistic(): void
    {
        $project = new Project();
        $statistic = new ProjectStatistic();
        $sut = new ProjectStatisticEvent($project, $statistic);

        self::assertSame($statistic, $sut->getStatistic());
        self::assertSame($project, $sut->getProject());
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());

        $begin = new \DateTimeImmutable('2020-08-08 12:34:56');
        $end = new \DateTimeImmutable('2020-09-08 12:34:56');
        $sut = new ProjectStatisticEvent($project, $statistic, $begin, $end);
        self::assertSame($begin, $sut->getBegin());
        self::assertSame($end, $sut->getEnd());
    }
}
