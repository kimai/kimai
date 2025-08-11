<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use App\Entity\Timesheet;
use App\Event\AbstractTimesheetEvent;
use App\Event\TimesheetCreatePreEvent;

#[CoversClass(AbstractTimesheetEvent::class)]
#[CoversClass(TimesheetCreatePreEvent::class)]
class TimesheetCreatePreEventTest extends AbstractTimesheetEventTestCase
{
    protected function createTimesheetEvent(Timesheet $timesheet): AbstractTimesheetEvent
    {
        return new TimesheetCreatePreEvent($timesheet);
    }
}
