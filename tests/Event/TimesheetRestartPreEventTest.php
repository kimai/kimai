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
use App\Event\TimesheetRestartPreEvent;

/**
 * @covers \App\Event\TimesheetRestartPreEvent
 */
class TimesheetRestartPreEventTest extends AbstractTimesheetEventTest
{
    protected function createTimesheetEvent(Timesheet $timesheet): AbstractTimesheetEvent
    {
        return new TimesheetRestartPreEvent($timesheet, new Timesheet());
    }

    public function testGetOriginalTimesheet()
    {
        $newTimesheet = new Timesheet();
        $originalTimesheet = new Timesheet();
        $sut = new TimesheetRestartPreEvent($newTimesheet, $originalTimesheet);

        self::assertSame($newTimesheet, $sut->getTimesheet());
        self::assertSame($originalTimesheet, $sut->getOriginalTimesheet());
    }
}
