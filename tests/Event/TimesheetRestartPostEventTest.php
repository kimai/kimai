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
use App\Event\TimesheetRestartPostEvent;

/**
 * @covers \App\Event\TimesheetRestartPostEvent
 */
class TimesheetRestartPostEventTest extends AbstractTimesheetEventTest
{
    protected function createTimesheetEvent(Timesheet $timesheet): AbstractTimesheetEvent
    {
        return new TimesheetRestartPostEvent($timesheet, new Timesheet());
    }

    public function testGetOriginalTimesheet()
    {
        $newTimesheet = new Timesheet();
        $originalTimesheet = new Timesheet();
        $sut = new TimesheetRestartPostEvent($newTimesheet, $originalTimesheet);

        self::assertSame($newTimesheet, $sut->getTimesheet());
        self::assertSame($originalTimesheet, $sut->getOriginalTimesheet());
    }
}
