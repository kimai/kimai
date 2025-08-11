<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use App\Entity\Activity;
use App\Event\AbstractActivityEvent;
use App\Event\ActivityUpdatePostEvent;

#[CoversClass(AbstractActivityEvent::class)]
#[CoversClass(ActivityUpdatePostEvent::class)]
class ActivityUpdatePostEventTest extends AbstractActivityEventTestCase
{
    protected function createActivityEvent(Activity $activity): AbstractActivityEvent
    {
        return new ActivityUpdatePostEvent($activity);
    }
}
