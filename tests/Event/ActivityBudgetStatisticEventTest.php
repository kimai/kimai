<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Activity;
use App\Event\ActivityBudgetStatisticEvent;
use App\Model\ActivityBudgetStatisticModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ActivityBudgetStatisticEvent
 */
class ActivityBudgetStatisticEventTest extends TestCase
{
    public function testStatistic(): void
    {
        $activity = $this->createMock(Activity::class);
        $activity->expects($this->exactly(2))->method('getId')->willReturn(12);

        $model1 = new ActivityBudgetStatisticModel($activity);
        $model2 = new ActivityBudgetStatisticModel(new Activity());
        $models = [
            $model1,
            4 => $model2,
        ];
        $begin = new \DateTime('-1 years');
        $end = new \DateTime();

        $sut = new ActivityBudgetStatisticEvent($models, $begin, $end);

        self::assertSame($models, $sut->getModels());
        self::assertNull($sut->getModel(1));
        self::assertSame($model1, $sut->getModel(12));
        self::assertSame($model2, $sut->getModel(4));
        self::assertEquals($begin, $sut->getBegin());
        self::assertEquals($end, $sut->getEnd());
    }
}
