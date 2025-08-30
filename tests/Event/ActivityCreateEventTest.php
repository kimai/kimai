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
use App\Event\ActivityCreateEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractActivityEvent::class)]
#[CoversClass(ActivityCreateEvent::class)]
class ActivityCreateEventTest extends AbstractActivityEventTestCase
{
    protected function createActivityEvent(Activity $activity): AbstractActivityEvent
    {
        return new ActivityCreateEvent($activity);
    }
}
