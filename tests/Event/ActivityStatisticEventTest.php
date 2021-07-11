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

    public function testStatistic()
    {
        $activity = new Activity();
        $statistic = new ActivityStatistic();
        $sut = new ActivityStatisticEvent($activity, $statistic);

        self::assertSame($statistic, $sut->getStatistic());
    }
}
