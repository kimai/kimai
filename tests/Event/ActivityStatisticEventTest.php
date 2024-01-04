<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Activity;
use App\Event\AbstractActivityEvent;
use App\Event\ActivityStatisticEvent;
use App\Model\ActivityStatistic;

/**
 * @covers \App\Event\AbstractActivityEvent
 * @covers \App\Event\ActivityStatisticEvent
 */
class ActivityStatisticEventTest extends AbstractActivityEventTest
{
    protected function createActivityEvent(Activity $activity): AbstractActivityEvent
    {
        return new ActivityStatisticEvent($activity, new ActivityStatistic());
    }

    public function testStatistic(): void
    {
        $activity = new Activity();
        $statistic = new ActivityStatistic();
        $sut = new ActivityStatisticEvent($activity, $statistic);

        self::assertSame($statistic, $sut->getStatistic());
        self::assertSame($activity, $sut->getActivity());
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());

        $begin = new \DateTime('2020-08-08 12:34:56');
        $end = new \DateTime('2020-09-08 12:34:56');
        $sut = new ActivityStatisticEvent($activity, $statistic, $begin, $end);
        self::assertEquals($begin, $sut->getBegin());
        self::assertEquals($end, $sut->getEnd());
    }
}
