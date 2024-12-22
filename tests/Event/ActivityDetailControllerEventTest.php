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
use App\Event\ActivityDetailControllerEvent;

/**
 * @covers \App\Event\AbstractActivityEvent
 * @covers \App\Event\ActivityDetailControllerEvent
 */
class ActivityDetailControllerEventTest extends AbstractActivityEventTestCase
{
    protected function createActivityEvent(Activity $activity): AbstractActivityEvent
    {
        return new ActivityDetailControllerEvent($activity);
    }

    public function testController(): void
    {
        /** @var ActivityDetailControllerEvent $event */
        $event = $this->createActivityEvent(new Activity());
        $event->addController('Foo\\Bar::helloWorld');
        self::assertEquals(['Foo\\Bar::helloWorld'], $event->getController());
    }
}
