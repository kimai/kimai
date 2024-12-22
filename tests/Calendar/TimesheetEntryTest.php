<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\TimesheetEntry;
use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\Timesheet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Calendar\TimesheetEntry
 */
class TimesheetEntryTest extends TestCase
{
    public function testConstruct(): void
    {
        $project = new Project();
        $activity = new Activity();
        $activity->setName('a wonderful activity!!');
        $activity->setProject($project);
        $timesheet = new Timesheet();
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);
        $timesheet->setDescription('hello foo bar');
        $timesheet->addTag((new Tag())->setName('bulb'));
        $timesheet->addTag((new Tag())->setName('action test'));

        $expectedData = [
            'activity' => null,
            'project' => null,
        ];

        $sut = new TimesheetEntry($timesheet, '#cccccc');

        self::assertEquals('a wonderful activity!!', $sut->getTitle());
        self::assertEquals('#cccccc', $sut->getColor());
        self::assertSame($project, $sut->getProject());
        self::assertSame($activity, $sut->getActivity());
        self::assertEquals('dd_timesheet', $sut->getBlockName());
        self::assertEquals($expectedData, $sut->getData());

        $expectedData = [
            'activity' => null,
            'project' => null,
            'tags' => 'bulb,action test',
            'description' => 'hello foo bar',
        ];

        $sut = new TimesheetEntry($timesheet, '#cccccc', true);

        self::assertEquals($expectedData, $sut->getData());
    }

    public function testEmpty(): void
    {
        $timesheet = new Timesheet();

        $expectedData = [
            'activity' => null,
            'project' => null,
        ];

        $sut = new TimesheetEntry($timesheet, '#ddd');

        self::assertEquals('', $sut->getTitle());
        self::assertEquals('#ddd', $sut->getColor());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getActivity());
        self::assertEquals($expectedData, $sut->getData());
    }

    public function testGetTitle(): void
    {
        $project = new Project();
        $project->setName('sdfsdf');
        $timesheet = new Timesheet();
        $timesheet->setProject($project);

        $sut = new TimesheetEntry($timesheet, '#ddd');
        self::assertEquals('sdfsdf', $sut->getTitle());

        $project = new Project();
        $timesheet = new Timesheet();
        $timesheet->setProject($project);

        $sut = new TimesheetEntry($timesheet, '#ddd');
        self::assertEquals('', $sut->getTitle());

        $project = new Project();
        $timesheet = new Timesheet();
        $timesheet->setDescription('fooooo');
        $timesheet->setProject($project);

        $sut = new TimesheetEntry($timesheet, '#ddd');
        self::assertEquals('fooooo', $sut->getTitle());
    }
}
