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
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractTimesheetEventTest extends TestCase
{
    abstract protected function createTimesheetEvent(Timesheet $timesheet): AbstractTimesheetEvent;

    public function testGetterAndSetter()
    {
        $timesheet = new Timesheet();
        $sut = $this->createTimesheetEvent($timesheet);

        self::assertInstanceOf(Event::class, $sut);
        self::assertSame($timesheet, $sut->getTimesheet());
    }
}
