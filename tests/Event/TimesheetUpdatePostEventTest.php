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
use App\Event\TimesheetUpdatePostEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractTimesheetEvent::class)]
#[CoversClass(TimesheetUpdatePostEvent::class)]
class TimesheetUpdatePostEventTest extends AbstractTimesheetEventTestCase
{
    protected function createTimesheetEvent(Timesheet $timesheet): AbstractTimesheetEvent
    {
        return new TimesheetUpdatePostEvent($timesheet);
    }
}
