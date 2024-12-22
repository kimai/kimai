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
    public function testConstruct(): void
    {
        $entries = [new TimesheetEntry(new Timesheet(), '#cccccc')];

        $sut = new RecentActivitiesSource($entries);

        self::assertEquals('calendar/drag-drop.html.twig', $sut->getBlockInclude());
        self::assertSame($entries, $sut->getEntries());
        self::assertEquals('POST', $sut->getMethod());
        self::assertEquals('post_timesheet', $sut->getRoute());
        self::assertEquals(['full' => 'true'], $sut->getRouteParams());
        self::assertEquals([], $sut->getRouteReplacer());
        self::assertEquals('recent.activities', $sut->getTitle());
    }
}
