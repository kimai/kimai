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
        self::assertEquals(0, $sut->getDurationDay());
        self::assertEquals(0, $sut->getDurationMonth());
        self::assertEquals(0, $sut->getDurationTotal());
        self::assertEquals(0, $sut->getDurationWeek());
        self::assertEquals(0, $sut->getNotExportedDuration());
        self::assertEquals(0, $sut->getNotExportedRate());
        self::assertEquals(0, $sut->getRateTotal());
    }

    public function testSetterGetter()
    {
        $sut = new ProjectViewModel(new Project());

        $sut->setDurationDay(123456789);
        $sut->setDurationMonth(23456789);
        $sut->setDurationTotal(3456789);
        $sut->setDurationWeek(456789);
        $sut->setNotExportedDuration(56789);
        $sut->setNotExportedRate(6789);
        $sut->setRateTotal(789);

        self::assertEquals(123456789, $sut->getDurationDay());
        self::assertEquals(23456789, $sut->getDurationMonth());
        self::assertEquals(3456789, $sut->getDurationTotal());
        self::assertEquals(456789, $sut->getDurationWeek());
        self::assertEquals(56789, $sut->getNotExportedDuration());
        self::assertEquals(6789, $sut->getNotExportedRate());
        self::assertEquals(789, $sut->getRateTotal());
    }
}
