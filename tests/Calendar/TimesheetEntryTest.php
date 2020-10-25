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
    public function testConstruct()
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
            'description' => 'hello foo bar',
            'activity' => null,
            'project' => null,
            'tags' => 'bulb,action test',
        ];

        $sut = new TimesheetEntry($timesheet, '#cccccc');

        $this->assertEquals('a wonderful activity!!', $sut->getTitle());
        $this->assertEquals('#cccccc', $sut->getColor());
        $this->assertSame($project, $sut->getProject());
        $this->assertSame($activity, $sut->getActivity());
        $this->assertEquals('dd_timesheet', $sut->getBlockName());
        $this->assertEquals($expectedData, $sut->getData());
    }

    public function testEmpty()
    {
        $timesheet = new Timesheet();

        $expectedData = [
            'description' => null,
            'activity' => null,
            'project' => null,
            'tags' => '',
        ];

        $sut = new TimesheetEntry($timesheet, '#ddd');

        $this->assertEquals('', $sut->getTitle());
        $this->assertEquals('#ddd', $sut->getColor());
        $this->assertNull($sut->getProject());
        $this->assertNull($sut->getActivity());
        $this->assertEquals($expectedData, $sut->getData());
    }

    public function testGetTitle()
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
