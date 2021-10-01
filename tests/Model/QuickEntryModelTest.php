<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\QuickEntryModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\QuickEntryModel
 */
class QuickEntryModelTest extends TestCase
{
    public function testEmptyModel()
    {
        $sut = new QuickEntryModel();
        self::assertTrue($sut->isPrototype());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getActivity());
        self::assertNull($sut->getUser());
        self::assertEquals([], $sut->getNewTimesheet());
        self::assertEquals([], $sut->getTimesheets());
        self::assertNull($sut->getLatestEntry());
        self::assertNull($sut->getFirstEntry());
        self::assertFalse($sut->hasNewTimesheet());
        self::assertFalse($sut->hasExistingTimesheet());
        self::assertFalse($sut->hasTimesheetWithDuration());
    }

    public function testFullModel()
    {
        $user = new User();
        $project = new Project();
        $activity = new Activity();

        $sut = new QuickEntryModel($user, $project, $activity);

        self::assertFalse($sut->hasNewTimesheet());
        $t = new Timesheet();
        $t->setDuration(null);
        $sut->addTimesheet($t);
        self::assertFalse($sut->hasNewTimesheet());
        $t = new Timesheet();
        $t->setDuration(1);
        $sut->addTimesheet($t);
        self::assertTrue($sut->hasNewTimesheet());
        self::assertCount(1, $sut->getNewTimesheet());

        self::assertFalse($sut->isPrototype());
        self::assertSame($project, $sut->getProject());
        self::assertSame($activity, $sut->getActivity());
        self::assertSame($user, $sut->getUser());

        $t1 = new Timesheet();
        $t1->setBegin(new \DateTime('2020-05-30'));
        $sut->addTimesheet($t1);
        $t2 = new Timesheet();
        $t2->setBegin(new \DateTime('2020-01-19'));
        $sut->addTimesheet($t2);
        self::assertSame($t1, $sut->getLatestEntry());
        self::assertSame($t2, $sut->getFirstEntry());

        self::assertCount(3, $sut->getNewTimesheet());
        self::assertCount(4, $sut->getTimesheets());

        self::assertFalse($sut->hasExistingTimesheet());
        self::assertTrue($sut->hasTimesheetWithDuration());
    }

    public function testDefaultModel()
    {
        $user = new User();
        $project = new Project();
        $activity = new Activity();

        $sut = new QuickEntryModel($user, $project, $activity);
        self::assertFalse($sut->isPrototype());
        self::assertSame($project, $sut->getProject());
        self::assertSame($activity, $sut->getActivity());
        self::assertSame($user, $sut->getUser());
    }
}
