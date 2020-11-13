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
use App\Event\TimesheetDeletePreEvent;

/**
 * @covers \App\Event\AbstractTimesheetEvent
 * @covers \App\Event\TimesheetDeletePreEvent
 */
class TimesheetDeletePreEventTest extends AbstractTimesheetEventTest
{
    protected function createTimesheetEvent(Timesheet $timesheet): AbstractTimesheetEvent
    {
        return new TimesheetDeletePreEvent($timesheet);
    }
}
