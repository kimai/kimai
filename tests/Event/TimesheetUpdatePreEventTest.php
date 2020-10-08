<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Timesheet;
use App\Event\AbstractTimesheetEvent;
use App\Event\TimesheetUpdatePreEvent;

/**
 * @covers \App\Event\AbstractTimesheetEvent
 * @covers \App\Event\TimesheetUpdatePreEvent
 */
class TimesheetUpdatePreEventTest extends AbstractTimesheetEventTest
{
    protected function createTimesheetEvent(Timesheet $timesheet): AbstractTimesheetEvent
    {
        return new TimesheetUpdatePreEvent($timesheet);
    }
}
