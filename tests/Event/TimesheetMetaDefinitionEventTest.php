<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Timesheet;
use App\Event\TimesheetMetaDefinitionEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\TimesheetMetaDefinitionEvent
 */
class TimesheetMetaDefinitionEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $timesheet = new Timesheet();
        $sut = new TimesheetMetaDefinitionEvent($timesheet);
        $this->assertSame($timesheet, $sut->getEntity());
    }
}
