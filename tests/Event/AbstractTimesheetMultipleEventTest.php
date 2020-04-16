<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Timesheet;
use App\Event\AbstractTimesheetMultipleEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractTimesheetMultipleEventTest extends TestCase
{
    abstract protected function createTimesheetMultipleEvent(array $timesheets): AbstractTimesheetMultipleEvent;

    public function testGetterAndSetter()
    {
        $timesheets = [new Timesheet(), new Timesheet()];
        $sut = $this->createTimesheetMultipleEvent($timesheets);

        self::assertInstanceOf(Event::class, $sut);
        self::assertSame($timesheets, $sut->getTimesheets());
    }
}
