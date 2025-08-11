<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use App\Event\AbstractTimesheetMultipleEvent;
use App\Event\TimesheetUpdateMultiplePostEvent;

#[CoversClass(AbstractTimesheetMultipleEvent::class)]
#[CoversClass(TimesheetUpdateMultiplePostEvent::class)]
class TimesheetUpdateMultiplePostEventTest extends AbstractTimesheetMultipleEventTestCase
{
    protected function createTimesheetMultipleEvent(array $timesheets): AbstractTimesheetMultipleEvent
    {
        return new TimesheetUpdateMultiplePostEvent($timesheets);
    }
}
