<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Project;
use App\Event\ProjectBudgetStatisticEvent;
use App\Model\ProjectBudgetStatisticModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ProjectBudgetStatisticEvent
 */
class ProjectBudgetStatisticEventTest extends TestCase
{
    public function testStatistic(): void
    {
        $project = $this->createMock(Project::class);
        $project->expects($this->exactly(2))->method('getId')->willReturn(12);

        $model1 = new ProjectBudgetStatisticModel($project);
        $model2 = new ProjectBudgetStatisticModel(new Project());
        $models = [
            $model1,
            4 => $model2,
        ];
        $begin = new \DateTime('-1 years');
        $end = new \DateTime();

        $sut = new ProjectBudgetStatisticEvent($models, $begin, $end);

        self::assertSame($models, $sut->getModels());
        self::assertNull($sut->getModel(1));
        self::assertSame($model1, $sut->getModel(12));
        self::assertSame($model2, $sut->getModel(4));
        self::assertEquals($begin, $sut->getBegin());
        self::assertEquals($end, $sut->getEnd());
    }
}
