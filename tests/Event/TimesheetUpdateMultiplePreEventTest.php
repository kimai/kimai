<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\AbstractTimesheetMultipleEvent;
use App\Event\TimesheetUpdateMultiplePreEvent;

/**
 * @covers \App\Event\AbstractTimesheetMultipleEvent
 * @covers \App\Event\TimesheetUpdateMultiplePreEvent
 */
class TimesheetUpdateMultiplePreEventTest extends AbstractTimesheetMultipleEventTestCase
{
    protected function createTimesheetMultipleEvent(array $timesheets): AbstractTimesheetMultipleEvent
    {
        return new TimesheetUpdateMultiplePreEvent($timesheets);
    }
}
