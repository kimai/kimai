<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\RecentActivitiesSource;
use App\Calendar\TimesheetEntry;
use App\Entity\Timesheet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Calendar\RecentActivitiesSource
 */
class RecentActivitiesSourceTest extends TestCase
{
    public function testConstruct()
    {
        $entries = [new TimesheetEntry(new Timesheet(), '#cccccc')];

        $sut = new RecentActivitiesSource($entries);

        $this->assertEquals('calendar/drag-drop.html.twig', $sut->getBlockInclude());
        $this->assertSame($entries, $sut->getEntries());
        $this->assertEquals('POST', $sut->getMethod());
        $this->assertEquals('post_timesheet', $sut->getRoute());
        $this->assertEquals(['full' => 'true'], $sut->getRouteParams());
        $this->assertEquals([], $sut->getRouteReplacer());
        $this->assertEquals('recent.activities', $sut->getTitle());
    }
}
