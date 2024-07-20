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
    public function testEmptyModel(): void
    {
        $user = new User();
        $sut = new QuickEntryModel($user);
        self::assertFalse($sut->isPrototype());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getActivity());
        self::assertEquals($user, $sut->getUser());
        self::assertEquals([], $sut->getNewTimesheet());
        self::assertEquals([], $sut->getTimesheets());
        self::assertNull($sut->getLatestEntry());
        self::assertNull($sut->getFirstEntry());
        self::assertFalse($sut->hasNewTimesheet());
        self::assertFalse($sut->hasExistingTimesheet());
        self::assertFalse($sut->hasTimesheetWithDuration());
    }

    public function testFullModel(): void
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
        $t3 = new Timesheet();
        $t3->setBegin(new \DateTime('2020-06-01'));
        $sut->addTimesheet($t3);
        $t4 = new Timesheet();
        $t4->setBegin(new \DateTime('2020-01-09'));
        $sut->addTimesheet($t4);
        self::assertSame($t3, $sut->getLatestEntry());
        self::assertEquals('2020-06-01', $sut->getLatestEntry()->getBegin()->format('Y-m-d'));
        self::assertSame($t4, $sut->getFirstEntry());
        self::assertEquals('2020-01-09', $sut->getFirstEntry()->getBegin()->format('Y-m-d'));

        self::assertCount(5, $sut->getNewTimesheet());
        self::assertCount(6, $sut->getTimesheets());

        self::assertFalse($sut->hasExistingTimesheet());
        self::assertTrue($sut->hasTimesheetWithDuration());

        $sut->setProject(null);
        self::assertNull($sut->getProject());
        $project2 = new Project();
        $sut->setProject($project2);
        self::assertSame($project2, $sut->getProject());

        $sut->setActivity(null);
        self::assertNull($sut->getActivity());
        $activity2 = new Activity();
        $sut->setActivity($activity2);
        self::assertSame($activity2, $sut->getActivity());

        $sut->setTimesheets([$t1, $t2, $t3, $t4]);
        self::assertCount(4, $sut->getNewTimesheet());
        self::assertCount(4, $sut->getTimesheets());
    }

    public function testHasExistingTimesheet(): void
    {
        $user = new User();
        $sut = new QuickEntryModel($user);

        self::assertFalse($sut->hasExistingTimesheet());
        $mock = $this->createMock(Timesheet::class);
        $mock->method('getId')->willReturn(1);
        $sut->addTimesheet($mock);
        self::assertTrue($sut->hasExistingTimesheet());
        self::assertFalse($sut->isPrototype());
    }

    public function testDefaultModel(): void
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

    public function testPrototype(): void
    {
        $user = new User();
        $sut = new QuickEntryModel($user);
        $sut->markAsPrototype();
        self::assertTrue($sut->isPrototype());

        $sut = new QuickEntryModel($user);
        $sut->markAsPrototype();
        $mock = $this->createMock(Timesheet::class);
        $mock->method('getId')->willReturn(1);
        $sut->addTimesheet($mock);
        self::assertTrue($sut->isPrototype());
    }
}
