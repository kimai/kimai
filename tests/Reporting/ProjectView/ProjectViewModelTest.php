<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\ProjectView;

use App\Entity\Project;
use App\Reporting\ProjectView\ProjectViewModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\ProjectView\ProjectViewModel
 */
class ProjectViewModelTest extends TestCase
{
    public function testDefaults()
    {
        $project = new Project();
        $sut = new ProjectViewModel($project);

        self::assertSame($project, $sut->getProject());
        self::assertSame(0, $sut->getDurationDay());
        self::assertSame(0, $sut->getDurationMonth());
        self::assertSame(0, $sut->getDurationTotal());
        self::assertSame(0, $sut->getDurationWeek());
        self::assertSame(0, $sut->getNotExportedDuration());
        self::assertSame(0.0, $sut->getNotExportedRate());
        self::assertSame(0.0, $sut->getRateTotal());
        self::assertNull($sut->getLastRecord());
        self::assertSame(0, $sut->getTimesheetCounter());
        self::assertNull($sut->getLastRecord());
        self::assertSame(0.0, $sut->getBillableRate());
        self::assertSame(0, $sut->getBillableDuration());
        self::assertSame(0, $sut->getNotBilledDuration());
        self::assertSame(0.0, $sut->getNotBilledRate());
    }

    public function testSetterGetter()
    {
        $sut = new ProjectViewModel(new Project());

        $date = new \DateTime();

        $sut->setDurationDay(123456789);
        $sut->setDurationMonth(23456789);
        $sut->setDurationTotal(3456789);
        $sut->setDurationWeek(456789);
        $sut->setNotExportedDuration(56789);
        $sut->setNotExportedRate(6789);
        $sut->setRateTotal(789);
        $sut->setLastRecord($date);

        self::assertSame(123456789, $sut->getDurationDay());
        self::assertSame(23456789, $sut->getDurationMonth());
        self::assertSame(3456789, $sut->getDurationTotal());
        self::assertSame(456789, $sut->getDurationWeek());
        self::assertSame(56789, $sut->getNotExportedDuration());
        self::assertSame(6789.0, $sut->getNotExportedRate());
        self::assertSame(789.0, $sut->getRateTotal());
        self::assertSame($date, $sut->getLastRecord());

        $date = new \DateTime();
        $sut->setTimesheetCounter(123);
        $sut->setLastRecord($date);
        $sut->setBillableRate(123.456);
        $sut->setBillableDuration(321);
        $sut->setNotBilledDuration(9876);
        $sut->setNotBilledRate(4705.23);

        self::assertSame(123, $sut->getTimesheetCounter());
        self::assertSame($date, $sut->getLastRecord());
        self::assertSame(123.456, $sut->getBillableRate());
        self::assertSame(321, $sut->getBillableDuration());
        self::assertSame(9876, $sut->getNotBilledDuration());
        self::assertSame(4705.23, $sut->getNotBilledRate());
    }
}
