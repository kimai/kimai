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
use App\Event\ActivityCreatePreEvent;

/**
 * @covers \App\Event\AbstractActivityEvent
 * @covers \App\Event\ActivityCreatePreEvent
 */
class ActivityCreatePreEventTest extends AbstractActivityEventTestCase
{
    protected function createActivityEvent(Activity $activity): AbstractActivityEvent
    {
        return new ActivityCreatePreEvent($activity);
    }
}
