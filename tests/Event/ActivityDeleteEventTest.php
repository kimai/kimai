<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Activity;
use App\Event\ActivityDeleteEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActivityDeleteEvent::class)]
class ActivityDeleteEventTest extends AbstractActivityEventTestCase
{
    protected function createActivityEvent(Activity $activity): ActivityDeleteEvent
    {
        return new ActivityDeleteEvent($activity);
    }

    public function testReplacement(): void
    {
        $activity = new Activity();
        $activity->setName('activity 1');
        $replacement = new Activity();
        $replacement->setName('activity 2');

        $sut = new ActivityDeleteEvent($activity, $replacement);

        self::assertSame($activity, $sut->getActivity());
        self::assertSame($replacement, $sut->getReplacementActivity());
    }
}
